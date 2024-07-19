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

namespace Rekalogika\Mapper\Tests\Fixtures\ObjectMapper;

class Person
{
    private bool $getIdIsCalled = false;

    private bool $getNameIsCalled = false;

    public function __construct(
        private readonly string $id,
        private readonly string $name,
    ) {
    }

    public function getId(): string
    {
        $this->getIdIsCalled = true;
        return $this->id;
    }

    public function getName(): string
    {
        $this->getNameIsCalled = true;
        return $this->name;
    }

    public function isGetNameIsCalled(): bool
    {
        return $this->getNameIsCalled;
    }

    public function isGetIdIsCalled(): bool
    {
        return $this->getIdIsCalled;
    }
}
