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

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\ObjectWithEnumProperty;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\ObjectWithNumericStringableProperty;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\ObjectWithStringableProperty;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\ObjectWithStringProperty;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeBackedEnum;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeEnum;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringableDto\ObjectWithFloatPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringableDto\ObjectWithIntegerPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringableDto\ObjectWithStringPropertyDto;

class ObjectEnumStringMappingTest extends FrameworkTestCase
{
    public function testStringableToString(): void
    {
        $object = new ObjectWithStringableProperty();
        $result = $this->mapper->map($object, ObjectWithStringPropertyDto::class);

        $this->assertEquals('foo', $result->property);
    }

    public function testStringableToInteger(): void
    {
        $object = new ObjectWithNumericStringableProperty();
        $result = $this->mapper->map($object, ObjectWithIntegerPropertyDto::class);

        $this->assertEquals(123456, $result->property);
    }

    public function testStringableToFloat(): void
    {
        $object = new ObjectWithNumericStringableProperty();
        $result = $this->mapper->map($object, ObjectWithFloatPropertyDto::class);

        $this->assertEquals(123456.789, $result->property);
    }

    public function testEnumToString(): void
    {
        $object = ObjectWithEnumProperty::preinitialized();
        $result = $this->mapper->map($object, ObjectWithStringProperty::class);

        $this->assertEquals('foo', $result->backedEnum);
        $this->assertEquals('Foo', $result->unitEnum);
    }

    public function testStringToEnum(): void
    {
        $object = ObjectWithStringProperty::preinitialized();
        $result = $this->mapper->map($object, ObjectWithEnumProperty::class);

        $this->assertEquals(SomeBackedEnum::Foo, $result->backedEnum);
        $this->assertEquals(SomeEnum::Foo, $result->unitEnum);
    }

    public function testInvalidStringToBackedEnum(): void
    {
        $this->expectException(\ValueError::class);
        $object = ObjectWithStringProperty::preinitialized();
        $object->backedEnum = 'invalid';
        $result = $this->mapper->map($object, ObjectWithEnumProperty::class);
        $this->assertEquals(SomeBackedEnum::Foo, $result->backedEnum);
    }

    public function testInvalidStringToUnitEnum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $object = ObjectWithStringProperty::preinitialized();
        $object->unitEnum = 'invalid';
        $result = $this->mapper->map($object, ObjectWithEnumProperty::class);
        $this->assertEquals(SomeEnum::Foo, $result->unitEnum);
    }
}
