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
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
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
        private PropertyAccessExtractorInterface $propertyAccessExtractor,
        private PropertyAccessorInterface $propertyAccessor,
        private TypeResolverInterface $typeResolver,
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

        // list properties

        $sourceProperties = $this->listSourceAttributes($sourceType, $context);
        $writableTargetProperties = $this
            ->listTargetWritableAttributes($targetType, $context);

        // calculate applicable properties

        $propertiesToMap = array_intersect($sourceProperties, $writableTargetProperties);

        // map properties

        foreach ($propertiesToMap as $propertyName) {
            assert(is_object($target));

            /** @var mixed */
            $targetPropertyValue = $this->resolveTargetPropertyValue(
                source: $source,
                target: $target,
                propertyName: $propertyName,
                targetClass: $targetClass,
                context: $context,
                path: $propertyName,
            );

            try {
                $this->propertyAccessor->setValue($target, $propertyName, $targetPropertyValue);
            } catch (AccessException | UnexpectedTypeException $e) {
                throw new UnableToWriteException($source, $target, $target, $propertyName, $e);
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
        string $propertyName,
        string $targetClass,
        Context $context,
        string $path,
    ): mixed {
        /** @var array<int,Type>|null */
        $targetPropertyTypes = $this->propertyTypeExtractor->getTypes($targetClass, $propertyName);

        if (null === $targetPropertyTypes || count($targetPropertyTypes) === 0) {
            throw new InvalidArgumentException(sprintf('Cannot get type of target property "%s::$%s".', $targetClass, $propertyName));
        }

        try {
            /** @var mixed */
            $sourcePropertyValue = $this->propertyAccessor->getValue($source, $propertyName);
        } catch (NoSuchPropertyException $e) {
            throw new IncompleteConstructorArgument($source, $targetClass, $propertyName, $e, context: $context);
        } catch (UninitializedPropertyException $e) {
            $sourcePropertyValue = null;
        } catch (AccessException | UnexpectedTypeException $e) {
            throw new UnableToReadException($source, $target, $source, $propertyName, $e, context: $context);
        }

        if ($target !== null) {
            try {
                /** @var mixed */
                $targetPropertyValue = $this->propertyAccessor->getValue($target, $propertyName);
            } catch (NoSuchPropertyException $e) {
                throw new IncompleteConstructorArgument($source, $targetClass, $propertyName, $e, context: $context);
            } catch (UninitializedPropertyException $e) {
                $targetPropertyValue = null;
            } catch (AccessException | UnexpectedTypeException $e) {
                throw new UnableToReadException($source, $target, $target, $propertyName, $e, context: $context);
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
                propertyName: $propertyName,
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
     * @return array<int,string>
     * @todo cache result
     */
    protected function listSourceAttributes(
        Type $sourceType,
        Context $context
    ): array {
        $class = $sourceType->getClassName();

        if (null === $class) {
            throw new InvalidArgumentException('Cannot get class name from source type.', context: $context);
        }

        $attributes = $this->propertyListExtractor->getProperties($class);

        if (null === $attributes) {
            throw new InvalidArgumentException(sprintf('Cannot get properties from source class "%s".', $class), context: $context);
        }

        $readableAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->propertyAccessExtractor->isReadable($class, $attribute)) {
                $readableAttributes[] = $attribute;
            }
        }

        return $readableAttributes;
    }

    /**
     * @return array<int,string>
     * @todo cache result
     */
    protected function listTargetWritableAttributes(
        Type $targetType,
        Context $context
    ): array {
        $class = $targetType->getClassName();

        if (null === $class) {
            throw new InvalidArgumentException('Cannot get class name from source type.', context: $context);
        }

        $attributes = $this->propertyListExtractor->getProperties($class);

        if (null === $attributes) {
            throw new InvalidArgumentException(sprintf('Cannot get properties from target class "%s".', $class), context: $context);
        }

        $writableAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->propertyAccessExtractor->isWritable($class, $attribute)) {
                $writableAttributes[] = $attribute;
            }
        }

        return $writableAttributes;
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
