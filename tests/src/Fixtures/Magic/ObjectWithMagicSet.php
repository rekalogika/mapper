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

namespace Rekalogika\Mapper\Tests\Fixtures\Magic;

class ObjectWithMagicSet
{
    private string $string;
    private \DateTimeImmutable $date;

    public function __set(string $name, mixed $value): void
    {
        match ($name) {
            // @phpstan-ignore assign.propertyType
            'string' => $this->string = $value,
            // @phpstan-ignore assign.propertyType
            'date' => $this->date = $value,
            default => throw new \BadMethodCallException(),
        };
    }

    public function getStringResult(): string
    {
        return $this->string;
    }

    public function getDateResult(): \DateTimeImmutable
    {
        return $this->date;
    }
}
