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

    private ?string $variadicName = null;

    /**
     * @var array<int,string>
     */
    private array $unsetSourceProperties = [];

    public function addArgument(string $name, mixed $value): void
    {
        if ($this->variadicName !== null) {
            throw new LogicException('Cannot add argument after variadic argument');
        }

        $this->contructorArguments[$name] = $value;
    }

    /**
     * @param iterable<mixed> $value
     */
    public function addVariadicArgument(string $name, iterable $value): void
    {
        if ($this->variadicName !== null) {
            throw new LogicException('Cannot add variadic argument after variadic argument');
        }

        $this->variadicName = $name;

        /** @var mixed $item */
        foreach ($value as $item) {
            $this->contructorArguments[] = $item;
        }
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

    /**
     * @return list<string>
     */
    public function getArgumentNames(): array
    {
        $argumentNames = [];

        foreach (array_keys($this->contructorArguments) as $name) {
            if (is_string($name)) {
                $argumentNames[] = $name;
            }
        }

        if ($this->variadicName !== null) {
            $argumentNames[] = $this->variadicName;
        }

        return $argumentNames;
    }
}
