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

use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\ObjectWithEnumStringableProperty;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeBackedEnum;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeEnum;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringableDto\ObjectWithEnumStringablePropertyDto;

/**
 * @internal
 */
class ObjectEnumStringMappingTest extends FrameworkTestCase
{
    public function testToString(): void
    {
        $object = new ObjectWithEnumStringableProperty();
        $result = $this->mapper->map($object, ObjectWithEnumStringablePropertyDto::class);

        $this->assertEquals('foo', $result->stringable);
        $this->assertEquals('foo', $result->backedEnum);
        $this->assertEquals('Foo', $result->unitEnum);
        $this->assertEquals(SomeBackedEnum::Foo, $result->stringBackedEnum);
        // $this->assertEquals(SomeEnum::Foo, $result->stringUnitEnum);
    }
}
