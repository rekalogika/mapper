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

use Symfony\Component\TypeInfo\Type;

/**
 * @internal
 * @implements \IteratorAggregate<int,SearchResultEntry>
 */
final readonly class SearchResult implements \IteratorAggregate, \Countable
{
    /**
     * @param list<Type> $sourceTypes
     * @param list<Type> $targetTypes
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
     * @return list<Type>
     */
    public function getSourceTypes(): array
    {
        return $this->sourceTypes;
    }

    /**
     * @return list<Type>
     */
    public function getTargetTypes(): array
    {
        return $this->targetTypes;
    }
}
