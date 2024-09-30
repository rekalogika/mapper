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
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectWithDateTimeImmutableDto;

class PropertyMapperWithDateTimeImmutable
{
    #[AsPropertyMapper(
        targetClass: SomeObjectWithDateTimeImmutableDto::class,
        property: 'property',
    )]
    public function mapPropertyA(
        SomeObject $object,
        \DateTimeImmutable $existing,
    ): \DateTimeImmutable {
        return new \DateTimeImmutable('2022-01-01 00:00:00');
    }
}
