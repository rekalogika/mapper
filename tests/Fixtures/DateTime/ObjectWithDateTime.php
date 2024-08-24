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

class ObjectWithDateTime
{
    public const DATETIME = '2024-01-01 00:00:00';

    public function getDateTimeImmutable(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::DATETIME);
    }
    public function getDateTime(): \DateTime
    {
        return new \DateTime(self::DATETIME);
    }

    public function getDatePoint(): DatePoint
    {
        return new DatePoint('2024-01-01');
    }
}
