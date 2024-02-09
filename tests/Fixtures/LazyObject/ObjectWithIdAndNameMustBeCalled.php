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

namespace Rekalogika\Mapper\Tests\Fixtures\LazyObject;

class ObjectWithIdAndNameMustBeCalled
{
    private bool $idCalled = false;
    private bool $nameCalled = false;

    public function getId(): string
    {
        $this->idCalled = true;

        return 'id';
    }

    public function getName(): string
    {
        $this->nameCalled = true;

        return 'name';
    }

    public function getOther(): string
    {
        return 'other';
    }

    public function isIdAndNameCalled(): bool
    {
        return $this->idCalled && $this->nameCalled;
    }
}
