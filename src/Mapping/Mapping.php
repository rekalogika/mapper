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

use Rekalogika\Mapper\Transformer\MixedType;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
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

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->entries;
    }

    public function addEntry(
        string $id,
        string $class,
        Type|MixedType $sourceType,
        Type|MixedType $targetType,
        string $sourceTypeString,
        string $targetTypeString,
        bool $variantTargetType,
    ): void {
        $entry = new MappingEntry(
            id: $id,
            class: $class,
            sourceType: $sourceType,
            targetType: $targetType,
            sourceTypeString: $sourceTypeString,
            targetTypeString: $targetTypeString,
            variantTargetType: $variantTargetType,
        );

        $this->entries[$entry->getOrder()] = $entry;
        $this->mappingBySourceAndTarget[$sourceTypeString][$targetTypeString][] = $entry;
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

        return $result;
    }
}
