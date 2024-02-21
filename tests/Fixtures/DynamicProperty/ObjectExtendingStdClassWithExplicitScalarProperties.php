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

namespace Rekalogika\Mapper\Tests\Fixtures\DynamicProperty;

class ObjectExtendingStdClassWithExplicitScalarProperties extends \stdClass
{
    private int $a = 1;
    private string $b = 'string';
    private bool $c = true;
    private float $d = 1.1;

    public function getA(): int
    {
        return $this->a;
    }

    public function setA(int $a): self
    {
        $this->a = $a;

        return $this;
    }

    public function getB(): string
    {
        return $this->b;
    }

    public function setB(string $b): self
    {
        $this->b = $b;

        return $this;
    }

    public function isC(): bool
    {
        return $this->c;
    }

    public function setC(bool $c): self
    {
        $this->c = $c;

        return $this;
    }

    public function getD(): float
    {
        return $this->d;
    }

    public function setD(float $d): self
    {
        $this->d = $d;

        return $this;
    }
}
