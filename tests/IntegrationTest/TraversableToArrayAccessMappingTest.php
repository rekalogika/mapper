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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Rekalogika\Mapper\Exception\MissingMemberValueTypeException;
use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayPropertyWithStringKey;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithTraversableProperties;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayAccessPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyDtoWithIntKey;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyWithoutTypeHintDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithCollectionPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesDto;

class TraversableToArrayAccessMappingTest extends AbstractIntegrationTest
{
    /**
     * @todo array with mixed target type means we should just copy the source
     */
    public function testFailedArrayToArray(): void
    {
        $source = new ObjectWithArrayProperty();

        // target array does not have information about the type of its
        // elements

        $this->expectException(MissingMemberValueTypeException::class);
        $result = $this->mapper->map($source, ObjectWithArrayPropertyWithoutTypeHintDto::class);
    }

    /**
     * @todo array with mixed target type means we should just copy the source
     */
    public function testFailedTraversableToArrayAccess(): void
    {
        $source = new ObjectWithTraversableProperties();

        // cannot do a direct mapping from array to \ArrayAccess because
        // it does not have information about the type of its elements

        $this->expectException(MissingMemberValueTypeException::class);
        $this->mapper->map($source, ObjectWithArrayPropertyWithoutTypeHintDto::class);
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

    public function testTraversableToCollectionDto(): void
    {
        $source = new ObjectWithTraversableProperties();

        $result = $this->mapper->map($source, ObjectWithCollectionPropertyDto::class);

        $this->assertInstanceOf(ObjectWithCollectionPropertyDto::class, $result);
        $property = $result->property;
        $this->assertInstanceOf(Collection::class, $property);
        $this->assertInstanceOf(ArrayCollection::class, $property);

        $property = $result->property;

        $member = $property?->get(1);
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $member);

        $this->assertEquals(1, $member->a);
        $this->assertEquals("string", $member->b);
        $this->assertEquals(true, $member->c);
        $this->assertEquals(1.1, $member->d);
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

    public function testArrayToCollectionDto(): void
    {
        $source = new ObjectWithArrayProperty();

        $result = $this->mapper->map($source, ObjectWithCollectionPropertyDto::class);

        $this->assertInstanceOf(ObjectWithCollectionPropertyDto::class, $result);
        $property = $result->property;
        $this->assertInstanceOf(Collection::class, $property);
        $this->assertInstanceOf(ArrayCollection::class, $property);

        $property = $result->property;

        $member = $property?->get(1);
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $member);

        $this->assertEquals(1, $member->a);
        $this->assertEquals("string", $member->b);
        $this->assertEquals(true, $member->c);
        $this->assertEquals(1.1, $member->d);
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
    // source has string key, target has int key
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
