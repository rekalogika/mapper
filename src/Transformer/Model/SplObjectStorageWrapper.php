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

namespace Rekalogika\Mapper\Transformer\Model;

/**
 * Fixes the iterator of SplObjectStorage.
 *
 * @template TKey of object
 * @template TValue
 * @implements \IteratorAggregate<TKey,TValue>
 * @implements \ArrayAccess<TKey,TValue>
 * @internal
 */
final readonly class SplObjectStorageWrapper implements
    \ArrayAccess,
    \IteratorAggregate,
    \Countable
{
    /**
     * @param \SplObjectStorage<TKey,TValue> $wrapped
     */
    public function __construct(
        private \SplObjectStorage $wrapped
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->wrapped->contains($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->wrapped->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert($offset !== null);
        $this->wrapped->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->wrapped->offsetUnset($offset);
    }

    /**
     * @return \Traversable<TKey,TValue>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->wrapped as $key) {
            yield $key => $this->wrapped->offsetGet($key);
        }
    }

    public function count(): int
    {
        return $this->wrapped->count();
    }
}
