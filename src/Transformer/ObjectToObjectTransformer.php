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
use Rekalogika\Mapper\Transformer\Exception\InvalidClassException;
use Rekalogika\Mapper\Transformer\Exception\NotAClassException;
use Rekalogika\Mapper\Transformer\Exception\UnableToReadException;
use Rekalogika\Mapper\Transformer\Exception\UnableToWriteException;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMappingResolverInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

final class ObjectToObjectTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private PropertyListExtractorInterface $propertyListExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyInitializableExtractorInterface $propertyInitializableExtractor,
        private PropertyAccessorInterface $propertyAccessor,
        private TypeResolverInterface $typeResolver,
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

        // get source object & class

        if (!is_object($source)) {
            throw new InvalidArgumentException(sprintf('The source must be an object, "%s" given.', get_debug_type($source)), context: $context);
        }

        $sourceType = $this->typeResolver->guessTypeFromVariable($source);

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

        // initialize target

        if (null === $target) {
            $target = $this->instantiateTarget($source, $targetType, $context);
        } else {
            if (!is_object($target)) {
                throw new InvalidArgumentException(sprintf('The target must be an object, "%s" given.', get_debug_type($target)), context: $context);
            }
        }

        // save object to cache

        $context(ObjectCache::class)
            ->saveTarget($source, $targetType, $target, $context);

        // resolve object mapping

        $objectMapping = $this->objectMappingResolver
            ->resolveObjectMapping($sourceType, $targetType, $context);

        // map properties

        foreach ($objectMapping->getPropertyMapping() as $propertyMapping) {
            assert(is_object($target));

            $sourcePropertyName = $propertyMapping->getSourcePath();
            $targetPropertyName = $propertyMapping->getTargetPath();

            /** @var mixed */
            $targetPropertyValue = $this->resolveTargetPropertyValue(
                source: $source,
                target: $target,
                sourcePropertyName: $sourcePropertyName,
                targetPropertyName: $targetPropertyName,
                targetClass: $targetClass,
                context: $context,
                path: $propertyMapping->getTargetPath(),
            );

            try {
                $this->propertyAccessor
                    ->setValue($target, $targetPropertyName, $targetPropertyValue);
            } catch (AccessException | UnexpectedTypeException $e) {
                throw new UnableToWriteException($source, $target, $target, $targetPropertyName, $e, context: $context);
            }
        }

        return $target;
    }

    /**
     * @param class-string $targetClass
     * @return mixed
     */
    private function resolveTargetPropertyValue(
        object $source,
        ?object $target,
        string $sourcePropertyName,
        string $targetPropertyName,
        string $targetClass,
        Context $context,
        string $path,
    ): mixed {
        /** @var array<int,Type>|null */
        $targetPropertyTypes = $this->propertyTypeExtractor->getTypes($targetClass, $targetPropertyName);

        if (null === $targetPropertyTypes || count($targetPropertyTypes) === 0) {
            throw new InvalidArgumentException(sprintf('Cannot get type of target property "%s::$%s".', $targetClass, $targetPropertyName), context: $context);
        }

        try {
            /** @var mixed */
            $sourcePropertyValue = $this->propertyAccessor
                ->getValue($source, $sourcePropertyName);
        } catch (NoSuchPropertyException $e) {
            throw new IncompleteConstructorArgument($source, $targetClass, $sourcePropertyName, $e, context: $context);
        } catch (UninitializedPropertyException $e) {
            $sourcePropertyValue = null;
        } catch (AccessException | UnexpectedTypeException $e) {
            throw new UnableToReadException($source, $target, $source, $sourcePropertyName, $e, context: $context);
        }

        if ($target !== null) {
            try {
                /** @var mixed */
                $targetPropertyValue = $this->propertyAccessor
                    ->getValue($target, $targetPropertyName);
            } catch (NoSuchPropertyException $e) {
                throw new IncompleteConstructorArgument($source, $targetClass, $targetPropertyName, $e, context: $context);
            } catch (UninitializedPropertyException $e) {
                $targetPropertyValue = null;
            } catch (AccessException | UnexpectedTypeException $e) {
                throw new UnableToReadException($source, $target, $target, $targetPropertyName, $e, context: $context);
            }
        } else {
            $targetPropertyValue = null;
        }

        /** @var mixed */
        $targetPropertyValue = $this->getMainTransformer()->transform(
            source: $sourcePropertyValue,
            target: $targetPropertyValue,
            targetTypes: $targetPropertyTypes,
            context: $context,
            path: $path,
        );

        return $targetPropertyValue;
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object(), true);
    }

    protected function instantiateTarget(
        object $source,
        Type $targetType,
        Context $context
    ): object {
        $targetClass = $targetType->getClassName();

        if (null === $targetClass || !\class_exists($targetClass)) {
            throw new InvalidClassException($targetType, context: $context);
        }

        $reflectionClass = new \ReflectionClass($targetClass);

        if (!$reflectionClass->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        $initializableTargetProperties = $this
            ->listTargetInitializableAttributes($targetClass, $context);

        $constructorArguments = [];

        foreach ($initializableTargetProperties as $propertyName) {
            /** @var mixed */
            $targetPropertyValue = $this->resolveTargetPropertyValue(
                source: $source,
                target: null,
                sourcePropertyName: $propertyName,
                targetPropertyName: $propertyName,
                targetClass: $targetClass,
                context: $context,
                path: $propertyName,
            );

            /** @psalm-suppress MixedAssignment */
            $constructorArguments[$propertyName] = $targetPropertyValue;
        }

        try {
            return $reflectionClass->newInstanceArgs($constructorArguments);
        } catch (\TypeError $e) {
            throw new InstantiationFailureException($source, $targetClass, $constructorArguments, $e, context: $context);
        }
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     * @todo cache result
     */
    protected function listTargetInitializableAttributes(
        string $class,
        Context $context
    ): array {
        $attributes = $this->propertyListExtractor->getProperties($class);

        if (null === $attributes) {
            throw new InvalidArgumentException(sprintf('Cannot get properties from target class "%s".', $class), context: $context);
        }

        $initializableAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->propertyInitializableExtractor->isInitializable($class, $attribute)) {
                $initializableAttributes[] = $attribute;
            }
        }

        return $initializableAttributes;
    }
}
