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

class ObjectWithPrivateConstructorDto
{
    public static function create(): self
    {
        return new self(
            a: 1,
            b: 'string',
            c: true,
            d: 1.1,
        );
    }

    private function __construct(
        private readonly int $a,
        private readonly string $b,
        private readonly bool $c,
        private readonly float $d,
    ) {}

    public function getA(): int
    {
        return $this->a;
    }

    public function getB(): string
    {
        return $this->b;
    }

    public function isC(): bool
    {
        return $this->c;
    }

    public function getD(): float
    {
        return $this->d;
    }
}
