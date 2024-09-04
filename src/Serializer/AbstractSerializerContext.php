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

namespace Rekalogika\Mapper\Serializer;

use Rekalogika\Mapper\Exception\InvalidArgumentException;

/**
 * @implements \ArrayAccess<string,mixed>
 * @implements \IteratorAggregate<string,mixed>
 * @deprecated
 */
abstract class AbstractSerializerContext implements
    \ArrayAccess,
    \IteratorAggregate,
    \Countable
{
    /**
     * @param array<string,mixed> $context
     */
    public function __construct(private array $context = []) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->context;
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->context[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->context[$offset] ?? null;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null === $offset) {
            throw new InvalidArgumentException('Offset cannot be null');
        }

        $this->context[$offset] = $value;
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->context[$offset]);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        yield from $this->context;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->context);
    }
}
