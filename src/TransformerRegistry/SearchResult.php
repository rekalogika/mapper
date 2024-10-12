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

namespace Rekalogika\Mapper\TransformerRegistry;

use Rekalogika\Mapper\Transformer\MixedType;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 * @implements \IteratorAggregate<int,SearchResultEntry>
 */
final class SearchResult implements \IteratorAggregate, \Countable
{
    /**
     * @param list<MixedType|Type> $sourceTypes
     * @param list<MixedType|Type> $targetTypes
     * @param array<int,SearchResultEntry> $entries
     */
    public function __construct(
        private array $sourceTypes,
        private array $targetTypes,
        private array $entries,
    ) {}

    #[\Override]
    public function count(): int
    {
        return \count($this->entries);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->entries;
    }

    /**
     * @return list<MixedType|Type>
     */
    public function getSourceTypes(): array
    {
        return $this->sourceTypes;
    }

    /**
     * @return list<MixedType|Type>
     */
    public function getTargetTypes(): array
    {
        return $this->targetTypes;
    }
}
