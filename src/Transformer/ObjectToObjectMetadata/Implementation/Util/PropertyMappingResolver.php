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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util;

use Rekalogika\Mapper\Attribute\Map;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;

/**
 * @internal
 */
final readonly class PropertyMappingResolver
{
    public function __construct(
        private PropertyListExtractorInterface $propertyListExtractor,
    ) {}

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @return array<int,array{string,string}>
     */
    public function getPropertiesToMap(
        string $sourceClass,
        string $targetClass,
        bool $targetAllowsDynamicProperties,
    ): array {
        $sourceProperties = $this->listProperties($sourceClass);
        $targetProperties = $this->listProperties($targetClass);

        $targetPropertyToSourceProperty = [];

        foreach ($targetProperties as $targetProperty) {
            $sourceProperty = $this->determinePairedProperty(
                class: $targetClass,
                property: $targetProperty,
                pairedClass: $sourceClass,
            );

            $targetPropertyToSourceProperty[$targetProperty] = $sourceProperty;
        }

        foreach ($sourceProperties as $sourceProperty) {
            $targetProperty = $this->determinePairedProperty(
                class: $sourceClass,
                property: $sourceProperty,
                pairedClass: $targetClass,
            );

            $targetPropertyToSourceProperty[$targetProperty] = $sourceProperty;
        }

        if ($targetAllowsDynamicProperties) {
            foreach ($targetProperties as $targetProperty) {
                $sourceProperty = $this->determinePairedProperty(
                    class: $targetClass,
                    property: $targetProperty,
                    pairedClass: $sourceClass,
                );

                $targetPropertyToSourceProperty[$targetProperty] = $sourceProperty;
            }
        }

        $map = [];

        foreach ($targetPropertyToSourceProperty as $targetProperty => $sourceProperty) {
            $map[] = [$sourceProperty, $targetProperty];
        }

        return $map;
    }

    /**
     * @param class-string $class
     * @param class-string $pairedClass
     */
    private function determinePairedProperty(
        string $class,
        string $property,
        string $pairedClass,
    ): string {
        $attributes = ClassUtil::getPropertyAttributes(
            class: $class,
            property: $property,
            attributeClass: Map::class,
            methodPrefixes: ['get', 'set', 'is', 'has', 'can'],
        );

        // process attributes with pairedClass first

        $attributesWithClass = array_values(array_filter(
            $attributes,
            fn(Map $attribute): bool => $attribute->class !== null && is_a($pairedClass, $attribute->class, true),
        ));

        if (\count($attributesWithClass) >= 1) {
            return $attributesWithClass[0]->property;
        }

        // process attributes without pairedClass

        $attributesWithoutClass = array_filter(
            $attributes,
            fn(Map $attribute): bool => $attribute->class === null,
        );

        if (\count($attributesWithoutClass) >= 1) {
            return $attributesWithoutClass[0]->property;
        }

        // if not found

        return $property;
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     */
    private function listProperties(
        string $class,
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        return array_values($properties);
    }
}
