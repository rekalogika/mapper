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
use Rekalogika\Mapper\MainTransformer\Exception\CannotFindTransformerException;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayPropertyWithStringKey;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithCollectionProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithNullCollectionProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithSplObjectStorageProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithTraversableProperties;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayAccessPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayAccessPropertyWithObjectKeyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayInterfacePropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyDtoWithIntKey;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyWithCompatibleHintDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayPropertyWithoutTypeHintDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArrayWithGetterNoSetterDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithCollectionPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithCollectionWithGetterNoSetterDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithNotNullArrayAccessPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;
use Rekalogika\Mapper\Transformer\Model\HashTable;
use Rekalogika\Mapper\Transformer\Model\LazyArray;

class TraversableToArrayAccessMappingTest extends FrameworkTestCase
{
    //
    // without typehint
    //

    public function testArrayToArrayWithoutTypehint(): void
    {
        $source = new ObjectWithArrayProperty();
        $result = $this->mapper->map($source, ObjectWithArrayPropertyWithoutTypeHintDto::class);

        $this->assertInstanceOf(ObjectWithArrayPropertyWithoutTypeHintDto::class, $result);
        $this->assertCount(3, $result->property);
        $this->assertArrayHasKey(0, $result->property);
        $this->assertArrayHasKey(1, $result->property);
        $this->assertArrayHasKey(2, $result->property);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[0]);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[1]);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[2]);
    }

    public function testTraversableToArrayAccessWithoutTypehint(): void
    {
        $source = new ObjectWithTraversableProperties();
        $result = $this->mapper->map($source, ObjectWithArrayPropertyWithoutTypeHintDto::class);

        $this->assertInstanceOf(ObjectWithArrayPropertyWithoutTypeHintDto::class, $result);
        $this->assertCount(3, $result->property);
        $this->assertArrayHasKey(0, $result->property);
        $this->assertArrayHasKey(1, $result->property);
        $this->assertArrayHasKey(2, $result->property);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[0]);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[1]);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[2]);
    }

    public function testTraversableToArrayAccessWithCompatibleTypehint(): void
    {
        $source = new ObjectWithTraversableProperties();
        $result = $this->mapper->map($source, ObjectWithArrayPropertyWithCompatibleHintDto::class);

        $this->assertInstanceOf(ObjectWithArrayPropertyWithCompatibleHintDto::class, $result);
        $this->assertCount(3, $result->property);
        $this->assertArrayHasKey(0, $result->property);
        $this->assertArrayHasKey(1, $result->property);
        $this->assertArrayHasKey(2, $result->property);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[0]);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[1]);
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $result->property[2]);
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

        $property = $result->property;
        $this->assertInstanceOf(LazyArray::class, $property);

        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertEquals(1, $result->property[1]?->a);
        $this->assertEquals("string", $result->property[1]?->b);
        $this->assertEquals(true, $result->property[1]?->c);
        $this->assertEquals(1.1, $result->property[1]?->d);
    }

    public function testCollectionToArrayAccessDto(): void
    {
        $source = new ObjectWithCollectionProperty();

        $result = $this->mapper->map($source, ObjectWithArrayAccessPropertyDto::class);

        $this->assertInstanceOf(ObjectWithArrayAccessPropertyDto::class, $result);

        $property = $result->property;
        $this->assertInstanceOf(LazyArray::class, $property);

        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertEquals(1, $result->property[1]?->a);
        $this->assertEquals("string", $result->property[1]?->b);
        $this->assertEquals(true, $result->property[1]?->c);
        $this->assertEquals(1.1, $result->property[1]?->d);
    }

    public function testNullToNotNullArrayAccessDto(): void
    {
        $source = new ObjectWithNullCollectionProperty();

        $result = $this->mapper->map($source, ObjectWithNotNullArrayAccessPropertyDto::class);

        $this->assertInstanceOf(ObjectWithNotNullArrayAccessPropertyDto::class, $result);

        $property = $result->property;
        $this->assertInstanceOf(LazyArray::class, $property);
        // @phpstan-ignore-next-line
        $this->assertInstanceOf(\ArrayAccess::class, $property);
    }

    public function testArrayToArrayInterfaceDto(): void
    {
        $source = new ObjectWithArrayProperty();

        $result = $this->mapper->map($source, ObjectWithArrayInterfacePropertyDto::class);

        $this->assertInstanceOf(ObjectWithArrayInterfacePropertyDto::class, $result);

        $property = $result->property;
        $this->assertInstanceOf(LazyArray::class, $property);

        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertEquals(1, $result->property[1]?->a);
        $this->assertEquals("string", $result->property[1]?->b);
        $this->assertEquals(true, $result->property[1]?->c);
        $this->assertEquals(1.1, $result->property[1]?->d);
    }

    public function testCollectionToArrayInterfaceDto(): void
    {
        $source = new ObjectWithCollectionProperty();

        $result = $this->mapper->map($source, ObjectWithArrayInterfacePropertyDto::class);

        $this->assertInstanceOf(ObjectWithArrayInterfacePropertyDto::class, $result);

        $property = $result->property;
        $this->assertInstanceOf(LazyArray::class, $property);

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
        $this->assertInstanceOf(LazyArray::class, $property);

        $property = $result->property;

        $member = $property?->get(1);
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $member);

        $this->assertEquals(1, $member->a);
        $this->assertEquals("string", $member->b);
        $this->assertEquals(true, $member->c);
        $this->assertEquals(1.1, $member->d);
    }

    public function testTraversableToArrayAccessWithObjectKeyDto(): void
    {
        $source = new ObjectWithSplObjectStorageProperty();

        $result = $this->mapper
            ->map($source, ObjectWithArrayAccessPropertyWithObjectKeyDto::class);

        $this->assertInstanceOf(ObjectWithArrayAccessPropertyWithObjectKeyDto::class, $result);
        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertInstanceOf(HashTable::class, $result->property);

        foreach ($result->property as $key => $value) {
            $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $key);
            $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $value);
        }
    }

    public function testTraversableToArrayAccessWithObjectKeyDtoButCannotBeTransformedIntoTargetType(): void
    {
        $source = new ObjectWithSplObjectStorageProperty();
        $this->expectException(CannotFindTransformerException::class);
        $this->expectExceptionMessage('Mapping path: "property(key)"');
        $result = $this->mapper
            ->map($source, ObjectWithArrayPropertyDto::class);
        $this->initialize($result);
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

    //
    // target existing object
    //

    public function testTraversableToExistingArrayAccessDto(): void
    {
        $source = new ObjectWithTraversableProperties();
        $target = new ObjectWithArrayAccessPropertyDto();

        /** @var \ArrayObject<int,ObjectWithScalarPropertiesDto> */
        $arrayObject = new \ArrayObject();
        $target->property = $arrayObject;

        $result = $this->mapper->map($source, $target);

        $this->assertInstanceOf(ObjectWithArrayAccessPropertyDto::class, $result);
        $this->assertInstanceOf(\ArrayAccess::class, $result->property);
        $this->assertEquals(1, $result->property[1]?->a);
        $this->assertEquals("string", $result->property[1]?->b);
        $this->assertEquals(true, $result->property[1]?->c);
        $this->assertEquals(1.1, $result->property[1]?->d);
    }

    public function testMappingToCollectionWithGetterButNoSetter(): void
    {
        $source = new ObjectWithTraversableProperties();
        $target = new ObjectWithCollectionWithGetterNoSetterDto();

        $this->assertCount(0, $target->getProperty());

        $result = $this->mapper->map($source, $target);

        $this->assertCount(3, $result->getProperty());

        $this->assertInstanceOf(ObjectWithCollectionWithGetterNoSetterDto::class, $result);

        $property = $result->getProperty();

        $member = $property->get(1);
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $member);

        $this->assertEquals(1, $member->a);
        $this->assertEquals("string", $member->b);
        $this->assertEquals(true, $member->c);
        $this->assertEquals(1.1, $member->d);
    }

    public function testMappingToArrayWithGetterButNoSetter(): void
    {
        $source = new ObjectWithTraversableProperties();
        $target = new ObjectWithArrayWithGetterNoSetterDto();

        $result = $this->mapper->map($source, $target);

        $this->assertLogContains('results in a different object instance from the original instance');
    }
}
