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
final class PropertyMappingResolver
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @return array<int,array{string,string}>
     */
    public static function resolvePropertiesToMap(
        PropertyListExtractorInterface $propertyListExtractor,
        string $sourceClass,
        string $targetClass,
        bool $targetAllowsDynamicProperties,
    ): array {
        $resolver = new self(
            propertyListExtractor: $propertyListExtractor,
            sourceClass: $sourceClass,
            targetClass: $targetClass,
            targetAllowsDynamicProperties: $targetAllowsDynamicProperties,
        );

        return $resolver->getPropertiesToMap();
    }

    /**
     * @var array<string,string>
     */
    private array $targetPropertyToSourceProperty = [];

    /**
     * @var array<int,string> $sourceProperties
     */
    private readonly array $sourceProperties;

    /**
     * @var array<int,string> $targetProperties
     */
    private readonly array $targetProperties;

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    private function __construct(
        private readonly PropertyListExtractorInterface $propertyListExtractor,
        private readonly string $sourceClass,
        private readonly string $targetClass,
        bool $targetAllowsDynamicProperties,
    ) {
        $this->sourceProperties = $this->listProperties($this->sourceClass);
        $this->targetProperties = $this->listProperties($this->targetClass);

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
