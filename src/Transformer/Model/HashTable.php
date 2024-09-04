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
 * An array-like object that accept an object as the key.
 *
 * @template TKey
 * @template TValue
 * @implements \Iterator<TKey,TValue>
 * @implements \ArrayAccess<TKey,TValue>
 * @internal
 */
final class HashTable implements
    \ArrayAccess,
    \Iterator,
    \Countable
{
    /**
     * @var array<string,TKey>
     */
    private array $keys = [];

    /**
     * @var array<string,TValue>
     */
    private array $values = [];

    private function generateId(mixed $variable): string
    {
        if (is_string($variable)) {
            return 'string:' . $variable;
        } elseif (is_int($variable)) {
            return 'int:' . $variable;
        } elseif (is_float($variable)) {
            return 'float:' . $variable;
        } elseif (is_bool($variable)) {
            return 'bool:' . ($variable ? 'true' : 'false');
        } elseif (is_object($variable)) {
            return 'object:' . spl_object_id($variable);
        } elseif (\is_resource($variable)) {
            return 'resource:' . \get_resource_id($variable);
        } elseif ($variable === null) {
            return 'null';
        }

        throw new \InvalidArgumentException(sprintf(
            'Unsupported variable typ "%s"',
            \get_debug_type($variable)
        ));
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->keys[$this->generateId($offset)]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        $id = $this->generateId($offset);

        if (!isset($this->keys[$id])) {
            throw new \OutOfBoundsException(sprintf(
                'Offset "%s" does not exist',
                $id
            ));
        }

        return $this->values[$id];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $id = $this->generateId($offset);

        // @phpstan-ignore-next-line
        $this->keys[$id] = $offset;
        $this->values[$id] = $value;
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        $id = $this->generateId($offset);

        unset($this->keys[$id], $this->values[$id]);
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->keys);
    }

    #[\Override]
    public function current(): mixed
    {
        // @phpstan-ignore-next-line
        return current($this->values);
    }

    #[\Override]
    public function next(): void
    {
        next($this->values);
        next($this->keys);
    }

    #[\Override]
    public function key(): mixed
    {
        // @phpstan-ignore-next-line
        return current($this->keys);
    }

    #[\Override]
    public function valid(): bool
    {
        return key($this->keys) !== null;
    }

    #[\Override]
    public function rewind(): void
    {
        reset($this->values);
        reset($this->keys);
    }

}
