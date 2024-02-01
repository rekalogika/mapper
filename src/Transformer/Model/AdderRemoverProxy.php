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
 */
class AdderRemoverProxy implements \ArrayAccess
{
    public function __construct(
        private object $hostObject,
        private ?string $adderMethodName,
        private ?string $removerMethodName,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        throw new LogicException('Not implemented');
    }

    public function offsetGet(mixed $offset): mixed
    {
        throw new LogicException('Not implemented');
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($this->adderMethodName === null) {
            throw new LogicException('Adder method is not available');
        }

        /** @psalm-suppress MixedMethodCall */
        $this->hostObject->{$this->adderMethodName}($value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->removerMethodName === null) {
            throw new LogicException('Remover method is not available');
        }

        /** @psalm-suppress MixedMethodCall */
        $this->hostObject->{$this->removerMethodName}($offset);
    }
}
