<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/mapper package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Rekalogika\Mapper\Transformer;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\IncompleteConstructorArgument;
use Rekalogika\Mapper\Transformer\Exception\InstantiationFailureException;
use Rekalogika\Mapper\Transformer\Exception\NotAClassException;
use Rekalogika\Mapper\Transformer\Exception\UnableToReadException;
use Rekalogika\Mapper\Transformer\Exception\UnableToWriteException;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMapping;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMappingResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;

final class ObjectToObjectTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private ObjectMappingResolverInterface $objectMappingResolver,
    ) {
    }

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // verify source

        if (!is_object($source)) {
            throw new InvalidArgumentException(sprintf('The source must be an object, "%s" given.', get_debug_type($source)), context: $context);
        }

        $sourceType = TypeGuesser::guessTypeFromVariable($source);
        $sourceClass = $sourceType->getClassName();

        if (null === $sourceClass || !\class_exists($sourceClass)) {
            throw new InvalidArgumentException("Cannot get the class name for the source type.", context: $context);
        }

        // verify target

        $targetClass = $targetType->getClassName();

        if (null === $targetClass) {
            throw new InvalidArgumentException("Cannot get the class name for the target type.", context: $context);
        }

        if (!\class_exists($targetClass)) {
            throw new NotAClassException($targetClass, context: $context);
        }

        // if sourceType and targetType are the same, just return the source

        if (null === $target && TypeCheck::isSomewhatIdentical($sourceType, $targetType)) {
            return $source;
        }

        // resolve object mapping

        $objectMapping = $this->objectMappingResolver
            ->resolveObjectMapping($sourceClass, $targetClass, $context);

        // initialize target

        if (null === $target) {
            $target = $this->instantiateTarget($source, $targetType, $objectMapping, $context);
        } else {
            if (!is_object($target)) {
                throw new InvalidArgumentException(sprintf('The target must be an object, "%s" given.', get_debug_type($target)), context: $context);
            }
        }

        // save object to cache

        $context(ObjectCache::class)
            ->saveTarget($source, $targetType, $target, $context);

        // map properties

        foreach ($objectMapping->getPropertyMapping() as $propertyMapping) {
            $sourcePropertyName = $propertyMapping->getSourceProperty();
            $targetPropertyName = $propertyMapping->getTargetProperty();
            $targetTypes = $propertyMapping->getTargetTypes();

            // get the value of the source property

            try {
                /** @var mixed */
                $sourcePropertyValue = $this->propertyAccessor
                    ->getValue($source, $sourcePropertyName);
            } catch (NoSuchPropertyException $e) {
                $sourcePropertyValue = null;
            } catch (UninitializedPropertyException $e) {
                continue;
            } catch (AccessException | UnexpectedTypeException $e) {
                $sourcePropertyValue = null;
            }

            // get the value of the target property

            try {
                /** @var mixed */
                $targetPropertyValue = $this->propertyAccessor
                    ->getValue($target, $targetPropertyName);
            } catch (UninitializedPropertyException $e) {
                $targetPropertyValue = null;
            } catch (NoSuchPropertyException | AccessException | UnexpectedTypeException $e) {
                $targetPropertyValue = null;
            }

            // transform the value

            /** @var mixed */
            $targetPropertyValue = $this->getMainTransformer()->transform(
                source: $sourcePropertyValue,
                target: $targetPropertyValue,
                targetTypes: $targetTypes,
                context: $context,
                path: $targetPropertyName,
            );

            try {
                $this->propertyAccessor
                    ->setValue($target, $targetPropertyName, $targetPropertyValue);
            } catch (AccessException | UnexpectedTypeException $e) {
                throw new UnableToWriteException(
                    $source,
                    $target,
                    $target,
                    $targetPropertyName,
                    $e,
                    context: $context
                );
            }
        }

        return $target;
    }

    protected function instantiateTarget(
        object $source,
        Type $targetType,
        ObjectMapping $objectMapping,
        Context $context
    ): object {
        $targetClass = $objectMapping->getTargetClass();

        // check if class is valid & instantiable

        if (!$objectMapping->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        // gets the mapping and loop over the mapping

        $initializableMappings = $objectMapping->getConstructorMapping();

        $constructorArguments = [];

        foreach ($initializableMappings as $mapping) {
            $sourcePropertyName = $mapping->getSourceProperty();
            $targetPropertyName = $mapping->getTargetProperty();
            $targetTypes = $mapping->getTargetTypes();

            if ($sourcePropertyName === null) {
                throw new IncompleteConstructorArgument($source, $targetClass, $targetPropertyName, context: $context);
            }

            // get the value of the source property

            try {
                /** @var mixed */
                $sourcePropertyValue = $this->propertyAccessor
                    ->getValue($source, $sourcePropertyName);
            } catch (NoSuchPropertyException $e) {
                // if source property is not found, then it is an error
                throw new IncompleteConstructorArgument($source, $targetClass, $sourcePropertyName, $e, context: $context);
            } catch (UninitializedPropertyException $e) {
                // if source property is unset, we skip it
                continue;
            } catch (AccessException | UnexpectedTypeException $e) {
                // otherwise, it is an error
                throw new UnableToReadException($source, null, $source, $sourcePropertyName, $e, context: $context);
            }

            // transform the value

            /** @var mixed */
            $targetPropertyValue = $this->getMainTransformer()->transform(
                source: $sourcePropertyValue,
                target: null,
                targetTypes: $targetTypes,
                context: $context,
                path: $targetPropertyName,
            );

            /** @psalm-suppress MixedAssignment */
            $constructorArguments[$targetPropertyName] = $targetPropertyValue;
        }

        try {
            $reflectionClass = new \ReflectionClass($targetClass);

            return $reflectionClass->newInstanceArgs($constructorArguments);
        } catch (\TypeError $e) {
            throw new InstantiationFailureException($source, $targetClass, $constructorArguments, $e, context: $context);
        }
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object(), true);
    }
}
