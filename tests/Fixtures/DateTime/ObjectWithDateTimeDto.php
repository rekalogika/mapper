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

namespace Rekalogika\Mapper\Tests\Fixtures\DateTime;

use Symfony\Component\Clock\DatePoint;

class ObjectWithDateTimeDto
{
    public const DATETIME = '2023-01-01 00:00:00';

    public static function getInitialized(): self
    {
        $dto = new self();
        $dto->dateTimeImmutable = new \DateTimeImmutable(self::DATETIME);
        $dto->dateTime = new \DateTime(self::DATETIME);
        $dto->datePoint = new DatePoint(self::DATETIME);

        return $dto;
    }

    public ?\DateTimeImmutable $dateTimeImmutable = null;

    public ?\DateTime $dateTime = null;

    public ?DatePoint $datePoint = null;
}
