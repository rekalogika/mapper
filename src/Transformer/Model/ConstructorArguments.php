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
 * @internal
 */
final class ConstructorArguments
{
    /**
     * @var array<int<0,max>|string,mixed>
     */
    private array $contructorArguments = [];

    private bool $variadicAdded = false;

    /**
     * @var array<int,string>
     */
    private array $unsetSourceProperties = [];

    public function addArgument(string $name, mixed $value): void
    {
        if ($this->variadicAdded) {
            throw new LogicException('Cannot add argument after variadic argument');
        }

        $this->contructorArguments[$name] = $value;
    }

    /**
     * @param iterable<mixed> $value
     */
    public function addVariadicArgument(iterable $value): void
    {
        /** @var mixed $item */
        foreach ($value as $item) {
            $this->contructorArguments[] = $item;
        }

        $this->variadicAdded = true;
    }

    public function addUnsetSourceProperty(string $name): void
    {
        $this->unsetSourceProperties[] = $name;
    }

    /**
     * @return array<int<0,max>|string,mixed>
     */
    public function getArguments(): array
    {
        return $this->contructorArguments;
    }

    /**
     * @return array<int,string>
     */
    public function getUnsetSourceProperties(): array
    {
        return $this->unsetSourceProperties;
    }

    public function hasArguments(): bool
    {
        return $this->contructorArguments !== [];
    }
}
