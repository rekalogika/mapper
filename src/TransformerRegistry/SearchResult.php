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

/**
 * @implements \IteratorAggregate<int,SearchResultEntry>
 */
class SearchResult implements \IteratorAggregate
{
    /**
     * @param iterable<int,SearchResultEntry> $entries
     */
    public function __construct(
        private iterable $entries
    ) {
    }

    public function getIterator(): \Traversable
    {
        yield from $this->entries;
    }
}
