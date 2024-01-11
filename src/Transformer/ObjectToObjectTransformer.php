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

use Rekalogika\Mapper\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Contracts\TransformerInterface;
use Rekalogika\Mapper\Contracts\TypeMapping;
use Rekalogika\Mapper\Exception\CachedTargetObjectNotFoundException;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\MainTransformer;
use Rekalogika\Mapper\Model\ObjectCache;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
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
        Type $sourceType,
        Type $targetType,
        array $context
    ): mixed {
        // get object cache

        if (!isset($context[MainTransformer::OBJECT_CACHE])) {
            $objectCache = new ObjectCache();
            $context[MainTransformer::OBJECT_CACHE] = $objectCache;
        } else {
            /** @var ObjectCache */
            $objectCache = $context[MainTransformer::OBJECT_CACHE];
        }

        // return from cache if already exists

        try {
            return $objectCache->getTarget($source, $targetType);
        } catch (CachedTargetObjectNotFoundException) {
        }

        // get source object & class

        if (!is_object($source)) {
            throw new InvalidArgumentException(sprintf('The source must be an object, "%s" given.', get_debug_type($source)));
        }

        $sourceType = $this->typeResolver->guessTypeFromVariable($source);

        $targetClass = $targetType->getClassName();

        if (null === $targetClass || !\class_exists($targetClass)) {
            throw new InvalidArgumentException('Cannot get class name from target type.');
        }

        // if sourceType and targetType are the same, just return the source

        if (null === $target && TypeCheck::isSomewhatIdentical($sourceType, $targetType)) {
            return $source;
        }

        // list properties

        $sourceProperties = $this->listSourceAttributes($sourceType, $context);
        $writableTargetProperties = $this
            ->listTargetWritableAttributes($targetType, $context);

        // initialize target, add to cache after initialization

        if (null === $target) {
            $objectCache->preCache($source, $targetType);
            $target = $this->initialize($targetType);
        } else {
            if (!is_object($target)) {
                throw new InvalidArgumentException(sprintf('The target must be an object, "%s" given.', get_debug_type($target)));
            }
        }

        $objectCache->saveTarget($source, $targetType, $target);

        // calculate applicable properties

        $propertiesToMap = array_intersect($sourceProperties, $writableTargetProperties);

        // map properties

        foreach ($propertiesToMap as $property) {
            /** @var array<int,Type>|null */
            $targetPropertyTypes = $this->propertyTypeExtractor->getTypes($targetClass, $property, $context);

            if (null === $targetPropertyTypes || count($targetPropertyTypes) === 0) {
                throw new InvalidArgumentException(sprintf('Cannot get type of target property "%s::$%s".', $targetClass, $property));
            }

            /** @var mixed */
            $sourcePropertyValue = $this->propertyAccessor->getValue($source, $property);
            /** @var mixed */
            $targetPropertyValue = $this->propertyAccessor->getValue($target, $property);

            /** @var mixed */
            $targetPropertyValue = $this->mainTransformer?->transform(
                source: $sourcePropertyValue,
                target: $targetPropertyValue,
                targetType: $targetPropertyTypes,
                context: $context
            );

            $this->propertyAccessor->setValue($target, $property, $targetPropertyValue);
        }

        return $target;
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object());
    }

    /**
     * @todo support constructor initialization
     */
    protected function initialize(Type $targetType): object
    {
        $class = $targetType->getClassName();

        if (null === $class || !\class_exists($class)) {
            throw new InvalidArgumentException('Cannot get class name from target type.');
        }

        // $initializableTargetProperties = $this->listTargetInitializableAttributes($targetClass);

        // $writableAndNotInitializableTargetProperties = array_diff(
        //     $writableTargetProperties,
        //     $initializableTargetProperties
        // );

        return (new \ReflectionClass($class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * @param array<string,mixed> $context
     * @return array<int,string>
     * @todo cache result
     */
    protected function listSourceAttributes(
        Type $sourceType,
        array $context
    ): array {
        $class = $sourceType->getClassName();

        if (null === $class) {
            throw new InvalidArgumentException('Cannot get class name from source type.');
        }

        $attributes = $this->propertyListExtractor->getProperties($class, $context);

        if (null === $attributes) {
            throw new InvalidArgumentException(sprintf('Cannot get properties from source class "%s".', $class));
        }

        $readableAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->propertyAccessExtractor->isReadable($class, $attribute, $context)) {
                $readableAttributes[] = $attribute;
            }
        }

        return $readableAttributes;
    }

    /**
     * @param array<string,mixed> $context
     * @return array<int,string>
     * @todo cache result
     */
    protected function listTargetWritableAttributes(
        Type $targetType,
        array $context
    ): array {
        $class = $targetType->getClassName();

        if (null === $class) {
            throw new InvalidArgumentException('Cannot get class name from source type.');
        }

        $attributes = $this->propertyListExtractor->getProperties($class, $context);

        if (null === $attributes) {
            throw new InvalidArgumentException(sprintf('Cannot get properties from target class "%s".', $class));
        }

        $writableAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->propertyAccessExtractor->isWritable($class, $attribute, $context)) {
                $writableAttributes[] = $attribute;
            }
        }

        return $writableAttributes;
    }

    /**
     * @param class-string $class
     * @param array<string,mixed> $context
     * @return array<int,string>
     * @todo cache result
     */
    protected function listTargetInitializableAttributes(string $class, array $context): array
    {
        $attributes = $this->propertyListExtractor->getProperties($class, $context);

        if (null === $attributes) {
            throw new InvalidArgumentException(sprintf('Cannot get properties from target class "%s".', $class));
        }

        $initializableAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->propertyInitializableExtractor->isInitializable($class, $attribute, $context)) {
                $initializableAttributes[] = $attribute;
            }
        }

        return $initializableAttributes;
    }
}
