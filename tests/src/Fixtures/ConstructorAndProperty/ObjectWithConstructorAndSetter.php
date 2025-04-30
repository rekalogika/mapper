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

namespace Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty;

class ObjectWithConstructorAndSetter
{
    /**
     * Different proxy behavior:
     *
     * * var-exporter: if all properties are eager, then the object will be
     *   uninitialized forever
     * * php: if all properties are eager, then the object becomes non-lazy
     *
     * This dummy variable is here to make sure the proxy behaves the same
     * way on different PHP versions
     */
    public string $foo = 'bar';

    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $description,
    ) {}

    public function setId(string $id): void
    {
        throw new \LogicException('This method should not be called');
    }

    public function setName(string $name): void
    {
        throw new \LogicException('This method should not be called');
    }

    public function setDescription(string $description): void
    {
        throw new \LogicException('This method should not be called');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
