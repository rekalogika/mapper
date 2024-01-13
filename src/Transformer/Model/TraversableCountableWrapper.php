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
 * @template TKey
 * @template TValue
 * @implements \IteratorAggregate<TKey,TValue>
 */
final class TraversableCountableWrapper implements \IteratorAggregate, \Countable
{
    /**
     * @param \Traversable<TKey,TValue> $traversable
     */
    public function __construct(
        private \Traversable $traversable,
        private \Countable|int $countable,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return $this->traversable;
    }

    public function count(): int
    {
        if (is_int($this->countable)) {
            return $this->countable;
        }

        return $this->countable->count();
    }
}
