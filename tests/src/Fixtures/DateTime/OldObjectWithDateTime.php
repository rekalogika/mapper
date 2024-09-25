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

class OldObjectWithDateTime
{
    public const DATETIME = '2024-01-01 00:00:00';

    public function getDateTimeInterface(): \DateTimeInterface
    {
        return new \DateTimeImmutable(self::DATETIME, new \DateTimeZone('UTC'));
    }

    public function getDateTimeImmutable(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::DATETIME, new \DateTimeZone('UTC'));
    }

    public function getDateTime(): \DateTime
    {
        return new \DateTime(self::DATETIME, new \DateTimeZone('UTC'));
    }

    public function getDatePoint(): DatePoint
    {
        return new DatePoint(self::DATETIME, new \DateTimeZone('UTC'));
    }
}
