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

use Rekalogika\Mapper\Exception\MissingMemberValueTypeException;
use Rekalogika\Mapper\Mapper;
use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayPropertyWithStringKey;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithTraversableProperties;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayAccessPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyDtoWithIntKey;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesDto;

class TraversableToArrayAccessMappingTest extends AbstractIntegrationTest
{
    public function testFailedArrayToArray(): void
    {
        $source = [
            'a' => 1,
            'b' => "string",
            'c' => true,
            'd' => 1.1,
        ];

        // target array does not have information about the type of its
        // elements

        $this->expectException(MissingMemberValueTypeException::class);
        $result = $this->mapper->map($source, 'array');
    }

    public function testFailedTraversableToArrayAccess(): void
    {
        $source = [
            'a' => 1,
            'b' => "string",
            'c' => true,
            'd' => 1.1,
        ];

        // cannot do a direct mapping from array to \ArrayAccess because
        // it does not have information about the type of its elements

        $this->expectException(MissingMemberValueTypeException::class);

        $result = $this->mapper->map(
            $source,
            \ArrayObject::class
        );
    }

    public function testArrayOfArrayToArrayOfDto(): void
    {
        $source = [
            [
                'a' => 1,
                'b' => "foo",
                'c' => true,
                'd' => 1.1,
            ],
            [
                'a' => 2,
                'b' => "bar",
                'c' => false,
                'd' => 0.1,
            ],
        ];

        $result = $this->mapper->map($source, 'array', [
            Mapper::TARGET_KEY_TYPE => 'string',
            Mapper::TARGET_VALUE_TYPE => ObjectWithScalarPropertiesDto::class,
        ]);

        // @phpstan-ignore-next-line
        $first = $result[0];
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $first);
        $this->assertSame(1, $first->a);
        $this->assertSame("foo", $first->b);
        $this->assertTrue($first->c);
        $this->assertSame(1.1, $first->d);

        // @phpstan-ignore-next-line
        $second = $result[1];
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $second);
        $this->assertSame(2, $second->a);
        $this->assertSame("bar", $second->b);
        $this->assertFalse($second->c);
        $this->assertSame(0.1, $second->d);
    }

    //
    // class property containing array-like
    //

    public function testTraversableToArrayAccessDto(): void
    {
        $source = new ObjectWithTraversableProperties();

        $result = $this->mapper->map($source, ObjectWithArrayAccessPropertyDto::class);

        $this->assertInstanceOf(ObjectWithArrayAccessPropertyDto::class, $result);
        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertEquals(1, $result->property[1]?->a);
        $this->assertEquals("string", $result->property[1]?->b);
        $this->assertEquals(true, $result->property[1]?->c);
        $this->assertEquals(1.1, $result->property[1]?->d);
    }

    public function testTraversableToArrayDto(): void
    {
        $source = new ObjectWithTraversableProperties();

        $result = $this->mapper->map($source, ObjectWithArrayPropertyDto::class);

        $one = $result->property[1] ?? null;
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $one);

        $this->assertEquals(1, $one->a);
        $this->assertEquals("string", $one->b);
        $this->assertEquals(true, $one->c);
        $this->assertEquals(1.1, $one->d);
    }

    public function testArrayToArrayAccessDto(): void
    {
        $source = new ObjectWithArrayProperty();

        $result = $this->mapper->map($source, ObjectWithArrayAccessPropertyDto::class);

        $this->assertInstanceOf(ObjectWithArrayAccessPropertyDto::class, $result);
        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertEquals(1, $result->property[1]?->a);
        $this->assertEquals("string", $result->property[1]?->b);
        $this->assertEquals(true, $result->property[1]?->c);
        $this->assertEquals(1.1, $result->property[1]?->d);
    }

    public function testArrayToArrayDto(): void
    {
        $source = new ObjectWithArrayProperty();

        $result = $this->mapper->map($source, ObjectWithArrayPropertyDto::class);

        $one = $result->property[1] ?? null;
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $one);

        $this->assertEquals(1, $one->a);
        $this->assertEquals("string", $one->b);
        $this->assertEquals(true, $one->c);
        $this->assertEquals(1.1, $one->d);
    }

    //
    // preinitialized
    //

    public function testTraversableToArrayAccessDtoPreInit(): void
    {
        $source = new ObjectWithTraversableProperties();

        $result = $this->mapper->map($source, ObjectWithArrayAccessPropertyDto::initialized());

        $this->assertInstanceOf(ObjectWithArrayAccessPropertyDto::class, $result);
        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertEquals(1, $result->property[1]?->a);
        $this->assertEquals("string", $result->property[1]?->b);
        $this->assertEquals(true, $result->property[1]?->c);
        $this->assertEquals(1.1, $result->property[1]?->d);
    }

    public function testTraversableToArrayDtoPreInit(): void
    {
        $source = new ObjectWithTraversableProperties();

        $result = $this->mapper->map($source, ObjectWithArrayPropertyDto::initialized());

        $one = $result->property[1] ?? null;
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $one);

        $this->assertEquals(1, $one->a);
        $this->assertEquals("string", $one->b);
        $this->assertEquals(true, $one->c);
        $this->assertEquals(1.1, $one->d);
    }

    public function testArrayToArrayAccessDtoPreInit(): void
    {
        $source = new ObjectWithArrayProperty();

        $result = $this->mapper->map($source, ObjectWithArrayAccessPropertyDto::initialized());
        $this->assertInstanceOf(ObjectWithArrayAccessPropertyDto::class, $result);
        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertEquals(1, $result->property[1]?->a);
        $this->assertEquals("string", $result->property[1]?->b);
        $this->assertEquals(true, $result->property[1]?->c);
        $this->assertEquals(1.1, $result->property[1]?->d);
    }

    public function testArrayToArrayDtoPreInit(): void
    {
        $source = new ObjectWithArrayProperty();

        $result = $this->mapper->map($source, ObjectWithArrayPropertyDto::initialized());

        $one = $result->property[1] ?? null;
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $one);

        $this->assertEquals(1, $one->a);
        $this->assertEquals("string", $one->b);
        $this->assertEquals(true, $one->c);
        $this->assertEquals(1.1, $one->d);
    }

    //
    //
    //

    public function testSourceStringKeyToTargetIntKey(): void
    {
        $source = new ObjectWithArrayPropertyWithStringKey();

        $result = $this->mapper->map($source, ObjectWithArrayPropertyDtoWithIntKey::class);

        $this->assertInstanceOf(ObjectWithArrayPropertyDtoWithIntKey::class, $result);
        $this->assertIsArray($result->property);
        $this->assertCount(3, $result->property);
        $this->assertArrayHasKey(0, $result->property);
        $this->assertArrayHasKey(1, $result->property);
        $this->assertArrayHasKey(2, $result->property);
    }



}
