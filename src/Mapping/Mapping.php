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

namespace Rekalogika\Mapper\Mapping;

/**
 * @implements \IteratorAggregate<int,MappingEntry>
 */
final class Mapping implements \IteratorAggregate
{
    /**
     * @var array<int,MappingEntry>
     */
    private array $entries = [];

    /**
     * @var array<string,array<string,array<int,MappingEntry>>>
     */
    private array $mappingBySourceAndTarget = [];

    public function getIterator(): \Traversable
    {
        yield from $this->entries;
    }

    public function addEntry(
        string $id,
        string $class,
        string $sourceType,
        string $targetType
    ): void {
        $entry = new MappingEntry(
            id: $id,
            class: $class,
            sourceType: $sourceType,
            targetType: $targetType
        );

        $this->entries[$entry->getOrder()] = $entry;
        $this->mappingBySourceAndTarget[$sourceType][$targetType][] = $entry;
    }

    /**
     * @param array<int,string> $sourceTypes
     * @param array<int,string> $targetTypes
     * @return array<int,MappingEntry>
     */
    public function getMappingBySourceAndTarget(
        array $sourceTypes,
        array $targetTypes
    ): array {
        $result = [];

        foreach ($sourceTypes as $sourceType) {
            foreach ($targetTypes as $targetType) {
                if (isset($this->mappingBySourceAndTarget[$sourceType][$targetType])) {
                    foreach ($this->mappingBySourceAndTarget[$sourceType][$targetType] as $mapper) {
                        $result[] = $mapper;
                    }
                }
            }
        }

        // sort by order

        usort(
            $result,
            fn (MappingEntry $a, MappingEntry $b)
            =>
            $a->getOrder() <=> $b->getOrder()
        );

        return $result;
    }
}
