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

namespace Rekalogika\Mapper\Tests\Fixtures\PropertyMapper;

class SomeObjectWithDateTimeDto
{
    // note: a mutable property
    private readonly \DateTime $property;

    public function __construct()
    {
        $this->property = new \DateTime('2021-01-01 00:00:00');
    }

    public function getProperty(): \DateTime
    {
        return $this->property;
    }

    // setter disabled
    // public function setProperty(\DateTime $property): void
    // {
    //     $this->property = $property;
    // }
}
