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
 * @implements \ArrayAccess<int,SearchResultEntry>
 */
class SearchResult implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @param array<int,SearchResultEntry> $entries
     */
    public function __construct(
        private array $entries
    ) {
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->entries[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->entries[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->entries[] = $value;
        } else {
            $this->entries[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->entries[$offset]);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->entries;
    }
}
