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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\PropertyMappingResolver;

use Rekalogika\Mapper\Attribute\Map;
use Rekalogika\Mapper\Transformer\Exception\PairedPropertyNotFoundException;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyMappingResolverInterface;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;

/**
 * @internal
 */
final readonly class PropertyMappingResolver implements PropertyMappingResolverInterface
{
    public function __construct(
        private PropertyListExtractorInterface $propertyListExtractor,
    ) {}

    #[\Override]
    public function getPropertiesToMap(
        string $sourceClass,
        string $targetClass,
        bool $targetAllowsDynamicProperties,
    ): array {
        $sourceProperties = $this->listProperties($sourceClass);
        $targetProperties = $this->listProperties($targetClass);

        $targetPropertyToSourceProperty = [];
        $skippedTargetProperties = [];

        foreach ($targetProperties as $targetProperty) {
            $sourceProperty = $this->determinePairedProperty(
                class: $targetClass,
                property: $targetProperty,
                pairedClass: $sourceClass,
                pairedClassProperties: $sourceProperties,
            );

            if ($sourceProperty === null) {
                $skippedTargetProperties[$targetProperty] = true;
                continue;
            }

            $targetPropertyToSourceProperty[$targetProperty] = $sourceProperty;
        }

        foreach ($sourceProperties as $sourceProperty) {
            $targetProperty = $this->determinePairedProperty(
                class: $sourceClass,
                property: $sourceProperty,
                pairedClass: $targetClass,
                pairedClassProperties: $targetProperties,
            );

            if (isset($skippedTargetProperties[$targetProperty])) {
                continue;
            }

            if ($targetProperty === null) {
                if (isset($targetPropertyToSourceProperty[$sourceProperty])) {
                    unset($targetPropertyToSourceProperty[$sourceProperty]);
                }

                continue;
            }

            $targetPropertyToSourceProperty[$targetProperty] = $sourceProperty;
        }

        if ($targetAllowsDynamicProperties) {
            foreach ($targetProperties as $targetProperty) {
                $sourceProperty = $this->determinePairedProperty(
                    class: $targetClass,
                    property: $targetProperty,
                    pairedClass: $sourceClass,
                    pairedClassProperties: $sourceProperties,
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
     * @param list<string> $pairedClassProperties
     */
    private function determinePairedProperty(
        string $class,
        string $property,
        string $pairedClass,
        array $pairedClassProperties,
    ): ?string {
        $attributes = ClassUtil::getPropertyAttributes(
            class: $class,
            property: $property,
            attributeClass: Map::class,
            methodPrefixes: ['get', 'set', 'is', 'has', 'can', 'with'],
            constructor: true,
        );

        // process attributes with pairedClass first

        $attributesWithClass = array_values(array_filter(
            $attributes,
            fn(Map $attribute): bool => $attribute->class !== null && is_a($pairedClass, $attribute->class, true),
        ));

        if (\count($attributesWithClass) >= 1) {
            $pairedProperty = $attributesWithClass[0]->property;

            if ($pairedProperty === null) {
                return null;
            }

            if (
                !$this->isPropertyPath($pairedProperty)
                && !\in_array($pairedProperty, $pairedClassProperties, true)
            ) {
                throw new PairedPropertyNotFoundException(
                    class: $class,
                    property: $property,
                    pairedClass: $pairedClass,
                    pairedProperty: $pairedProperty,
                );
            }

            return $pairedProperty;
        }

        // process attributes without pairedClass

        $attributesWithoutClass = array_filter(
            $attributes,
            fn(Map $attribute): bool => $attribute->class === null,
        );

        if (\count($attributesWithoutClass) >= 1) {
            $pairedProperty = $attributesWithoutClass[0]->property;

            if ($pairedProperty === null) {
                return null;
            }

            if (
                !$this->isPropertyPath($pairedProperty)
                && !\in_array($pairedProperty, $pairedClassProperties, true)
            ) {
                throw new PairedPropertyNotFoundException(
                    class: $class,
                    property: $property,
                    pairedClass: $pairedClass,
                    pairedProperty: $pairedProperty,
                );
            }

            return $pairedProperty;
        }

        // if not found

        return $property;
    }

    /**
     * @param class-string $class
     * @return list<string>
     */
    private function listProperties(
        string $class,
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        return array_values($properties);
    }

    private function isPropertyPath(string $property): bool
    {
        return str_contains($property, '.') || str_contains($property, '[');
    }
}
