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

namespace Rekalogika\Mapper\Tests\Services\PropertyMapper;

use Rekalogika\Mapper\Attribute\AsPropertyMapper;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\ObjectWithDateTime;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\ObjectWithDateTimeImmutable;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectDto;

class DateTimePropertyMapper
{
    #[AsPropertyMapper(
        targetClass: ObjectWithDateTime::class,
        property: 'dateTime',
    )]
    public function mapDateTime(
        SomeObject $object,
        \DateTime $currentValue
    ): \DateTime {
        $currentValue->setDate(1999, 2, 3);

        return $currentValue;
    }

    #[AsPropertyMapper(
        targetClass: ObjectWithDateTimeImmutable::class,
        property: 'dateTime',
    )]
    public function mapDateTimeImmutable(
        SomeObject $object,
        \DateTimeImmutable $currentValue
    ): \DateTimeImmutable {
        $currentValue = $currentValue->setDate(1999, 2, 3);

        return $currentValue;
    }
}
