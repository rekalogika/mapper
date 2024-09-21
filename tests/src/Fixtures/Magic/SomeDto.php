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

final class SomeDto
{
    public ?string $string = null;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $generatesException;

    public static function prefilled(): self
    {
        $dto = new self();
        $dto->string = 'Hello';
        $dto->date = new \DateTimeImmutable('2021-01-01');
        $dto->generatesException = new \DateTimeImmutable('2021-01-01');

        return $dto;
    }
}
