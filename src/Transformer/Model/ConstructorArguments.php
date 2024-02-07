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

class ConstructorArguments
{
    /**
     * @var array<string,mixed>
     */
    private array $contructorArguments = [];

    /**
     * @var array<int,string>
     */
    private array $unsetSourceProperties = [];

    public function addArgument(string $name, mixed $value): void
    {
        $this->contructorArguments[$name] = $value;
    }

    public function addUnsetSourceProperty(string $name): void
    {
        $this->unsetSourceProperties[] = $name;
    }

    /**
     * @return array<string,mixed>
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
}
