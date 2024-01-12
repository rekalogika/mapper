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

class ObjectWithConstructorAndMoreArgumentDto
{
    public function __construct(
        private int $a,
        private string $b,
        private bool $c,
        private float $d,
        // uses object to prevent casting
        private \Stringable $e,
    ) {
    }

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

    // work around mapping null to object, now property info sees it as a string
    public function getE(): string
    {
        return (string) $this->e;
    }
}
