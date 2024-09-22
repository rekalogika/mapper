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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Util;

use Rekalogika\Mapper\Attribute\Map;
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final class PropertyMappingResolver
{
    /**
     * @var array<string,string>
     */
    private array $targetPropertyToSourceProperty = [];

    /**
     * @param class-string $sourceClass
     * @param array<int,string> $sourceProperties
     * @param class-string $targetClass
     * @param array<int,string> $targetProperties
     */
    public function __construct(
        private readonly string $sourceClass,
        private readonly array $sourceProperties,
        private readonly string $targetClass,
        private readonly array $targetProperties,
        bool $targetAllowsDynamicProperties,
    ) {
        $this->processTargetProperties();
        $this->processSourceProperties();

        if ($targetAllowsDynamicProperties) {
            $this->processDynamicProperties();
        }
    }

    private function processSourceProperties(): void
    {
        foreach ($this->sourceProperties as $sourceProperty) {
            $targetProperty = $this->determinePairedProperty(
                class: $this->sourceClass,
                property: $sourceProperty,
                pairedClass: $this->targetClass,
            );

            $this->targetPropertyToSourceProperty[$targetProperty] = $sourceProperty;
        }
    }

    private function processTargetProperties(): void
    {
        foreach ($this->targetProperties as $targetProperty) {
            $sourceProperty = $this->determinePairedProperty(
                class: $this->targetClass,
                property: $targetProperty,
                pairedClass: $this->sourceClass,
            );

            $this->targetPropertyToSourceProperty[$targetProperty] = $sourceProperty;
        }
    }

    private function processDynamicProperties(): void
    {
        foreach ($this->sourceProperties as $sourceProperty) {
            if (!isset($this->targetPropertyToSourceProperty[$sourceProperty])) {
                $this->targetPropertyToSourceProperty[$sourceProperty] = $sourceProperty;
            }
        }
    }

    /**
     * @return array<int,array{string,string}>
     */
    public function getPropertiesToMap(): array
    {
        $map = [];

        foreach ($this->targetPropertyToSourceProperty as $targetProperty => $sourceProperty) {
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
        $attributes = ClassUtil::getAttributes(
            class: $class,
            property: $property,
            attributeClass: Map::class,
            methodPrefixes: ['get', 'set', 'is', 'has', 'can'],
        );

        // process attributes with pairedClass first

        $attributesWithClass = array_filter(
            $attributes,
            fn(Map $attribute): bool => $attribute->class === $pairedClass,
        );

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
}
