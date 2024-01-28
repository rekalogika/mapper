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

namespace Rekalogika\Mapper\Transformer\ObjectMappingResolver;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ConstructorMapping;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMapping;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMappingResolverInterface;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\PropertyMapping;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

final class ObjectMappingResolver implements ObjectMappingResolverInterface
{
    public function __construct(
        private PropertyAccessExtractorInterface $propertyAccessExtractor,
        private PropertyListExtractorInterface $propertyListExtractor,
        private PropertyInitializableExtractorInterface $propertyInitializableExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
    ) {
    }

    public function resolveObjectMapping(
        string $sourceClass,
        string $targetClass,
        Context $context
    ): ObjectMapping {
        $objectMapping = new ObjectMapping($sourceClass, $targetClass);

        // queries

        $readableSourceProperties = $this
            ->listReadableSourceProperties($sourceClass, $context);
        $writableTargetProperties = $this
            ->listWritableTargetProperties($targetClass, $context);
        $initializableTargetProperties = $this
            ->listInitializableTargetProperties($targetClass, $context);

        // determine if targetClass is instantiable

        $reflectionClass = new \ReflectionClass($targetClass);
        $instantiable = $reflectionClass->isInstantiable();
        $objectMapping->setInstantiable($instantiable);

        // process properties mapping

        foreach ($writableTargetProperties as $targetProperty) {
            if (!in_array($targetProperty, $readableSourceProperties)) {
                continue;
            }

            $sourceProperty = $targetProperty;

            ///

            $targetPropertyTypes = $this->propertyTypeExtractor
                ->getTypes($targetClass, $targetProperty);

            if (null === $targetPropertyTypes || count($targetPropertyTypes) === 0) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot get type of target property "%s::$%s".',
                        $targetClass,
                        $targetProperty
                    ),
                    context: $context
                );
            }

            $objectMapping->addPropertyMapping(new PropertyMapping(
                $sourceProperty,
                $targetProperty,
                $targetPropertyTypes,
            ));
        }

        // process source properties to target constructor mapping

        foreach ($initializableTargetProperties as $property) {
            $sourceProperty = $property;
            $targetProperty = $property;

            ///

            if (!in_array($property, $readableSourceProperties)) {
                $sourceProperty = null;
            }

            $targetPropertyTypes = $this->propertyTypeExtractor
                ->getTypes($targetClass, $targetProperty);

            if (null === $targetPropertyTypes || count($targetPropertyTypes) === 0) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot get type of target property "%s::$%s".',
                        $targetClass,
                        $targetProperty
                    ),
                    context: $context
                );
            }

            $objectMapping->addConstructorMapping(new ConstructorMapping(
                $sourceProperty,
                $targetProperty,
                $targetPropertyTypes,
            ));
        }

        return $objectMapping;
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     */
    private function listReadableSourceProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        $readableProperties = [];

        foreach ($properties as $property) {
            if ($this->propertyAccessExtractor->isReadable($class, $property)) {
                $readableProperties[] = $property;
            }
        }

        return $readableProperties;
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     */
    private function listWritableTargetProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        $writableProperties = [];

        foreach ($properties as $property) {
            if ($this->propertyAccessExtractor->isWritable($class, $property)) {
                $writableProperties[] = $property;
            }
        }

        return $writableProperties;
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     */
    private function listInitializableTargetProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        $initializableProperties = [];

        foreach ($properties as $property) {
            if ($this->propertyInitializableExtractor->isInitializable($class, $property)) {
                $initializableProperties[] = $property;
            }
        }

        return $initializableProperties;
    }
}
