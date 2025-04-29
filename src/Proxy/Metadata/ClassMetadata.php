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

namespace Rekalogika\Mapper\Proxy\Metadata;

use Rekalogika\Mapper\Exception\LogicException;

/**
 * @internal
 */
final readonly class ClassMetadata
{
    /**
     * @var array<string,list<PropertyMetadata>> $properties
     */
    private array $properties;

    /**
     * @param class-string $class
     * @param list<PropertyMetadata> $properties
     */
    public function __construct(
        private string $class,
        private bool $allowsDynamicProperties,
        array $properties,
    ) {
        $newProperties = [];

        foreach ($properties as $property) {
            $newProperties[$property->getName()][] = $property;
        }

        $this->properties = $newProperties;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return list<PropertyMetadata>
     */
    public function getPropertiesByName(string $name): array
    {
        return $this->properties[$name] ?? throw new LogicException(\sprintf(
            'Property "%s" not found in class "%s"',
            $name,
            $this->class,
        ));
    }

    /**
     * Converts a list of property names to an array suitable as the input for
     * LazyGhostTrait::createLazyGhost().
     *
     * @param list<string> $eagerProperties
     * @return array<string,true>
     */
    public function getVarExporterSkippedProperties(array $eagerProperties): array
    {
        $skippedProperties = [];

        foreach ($eagerProperties as $name) {
            $properties = $this->properties[$name] ?? [];

            foreach ($properties as $property) {
                $skippedProperties[$property->getScopeNotation()] = true;
            }
        }

        return $skippedProperties;
    }

    /**
     * @param list<string> $eagerProperties
     * @return list<PropertyMetadata>
     */
    public function getPropertyMetadatas(array $eagerProperties): array
    {
        $properties = [];

        foreach ($eagerProperties as $name) {
            $properties2 = $this->properties[$name] ?? [];
            $properties = array_merge($properties, $properties2);
        }

        return array_values(array_unique($properties, SORT_REGULAR));
    }

    public function allowsDynamicProperties(): bool
    {
        return $this->allowsDynamicProperties;
    }
}
