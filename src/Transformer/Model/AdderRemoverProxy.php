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

use Rekalogika\Mapper\Exception\LogicException;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements \ArrayAccess<TKey,TValue>
 * @implements \IteratorAggregate<TKey,TValue>
 * @internal
 */
final readonly class AdderRemoverProxy implements
    \ArrayAccess,
    \IteratorAggregate,
    \Countable
{
    public function __construct(
        private object $hostObject,
        private ?string $getterMethodName,
        private ?string $adderMethodName,
        private ?string $removerMethodName,
    ) {
    }

    /**
     * @return \ArrayAccess<TKey,TValue>|array<TKey,TValue>
     */
    private function getCollection(): mixed
    {
        if ($this->getterMethodName === null) {
            throw new LogicException('Getter method is not available');
        }

        /** @psalm-suppress MixedMethodCall */
        $result = $this->hostObject->{$this->getterMethodName}();

        if (!\is_array($result) && !$result instanceof \ArrayAccess) {
            throw new LogicException('Value is not an array or ArrayAccess');
        }

        /** @var \ArrayAccess<TKey,TValue>|array<TKey,TValue> $result */

        return $result;
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        $value = $this->getCollection();

        if ($value instanceof \Traversable) {
            return $value;
        } elseif (\is_array($value)) {
            return new \ArrayIterator($value);
        }

        throw new LogicException('Value is not traversable or array');
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->getCollection()[$offset]);
    }

    /** @psalm-suppress MixedInferredReturnType */
    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getCollection()[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($this->adderMethodName === null) {
            throw new LogicException('Adder method is not available');
        }

        /** @psalm-suppress MixedMethodCall */
        $this->hostObject->{$this->adderMethodName}($value);
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        if ($this->removerMethodName === null) {
            throw new LogicException('Remover method is not available');
        }

        $value = $this->getCollection()[$offset];

        /** @psalm-suppress MixedMethodCall */
        $this->hostObject->{$this->removerMethodName}($value);
    }

    #[\Override]
    public function count(): int
    {
        $value = $this->getCollection();

        if ($value instanceof \Countable) {
            return $value->count();
        }

        throw new LogicException('Value is not countable');
    }
}
