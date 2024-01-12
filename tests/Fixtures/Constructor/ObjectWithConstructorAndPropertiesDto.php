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

namespace Rekalogika\Mapper\Tests\Fixtures\Constructor;

class ObjectWithConstructorAndPropertiesDto
{
    public function __construct(
        private int $a = 1,
        private string $b = 'string',
    ) {
    }

    private ?bool $c = null;
    private ?float $d = null;

    public function getA(): int
    {
        return $this->a;
    }

    public function getB(): string
    {
        return $this->b;
    }

    public function isC(): ?bool
    {
        return $this->c;
    }

    public function getD(): ?float
    {
        return $this->d;
    }

    public function setC(?bool $c): self
    {
        $this->c = $c;

        return $this;
    }

    public function setD(?float $d): self
    {
        $this->d = $d;

        return $this;
    }
}
