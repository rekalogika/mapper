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

namespace Rekalogika\Mapper\Tests\IntegrationTest;

use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\ObjectWithEnumStringableProperty;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringableDto\ObjectWithEnumStringablePropertyDto;

class ObjectEnumStringMappingTest extends AbstractIntegrationTest
{
    public function testToString(): void
    {
        $object = new ObjectWithEnumStringableProperty();
        $result = $this->mapper->map($object, ObjectWithEnumStringablePropertyDto::class);

        $this->assertEquals('foo', $result->stringable);
        $this->assertEquals('foo', $result->backedEnum);
        $this->assertEquals('Foo', $result->unitEnum);
    }
}
