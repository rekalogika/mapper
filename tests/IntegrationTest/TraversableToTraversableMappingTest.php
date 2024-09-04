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
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithLazyDoctrineCollectionProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithLazyDoctrineCollectionWithPresetCountableProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithNullCollectionProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithTraversableProperties;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithNotNullTraversablePropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithTraversablePropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithTraversablePropertyWithoutTypeHintDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;
use Rekalogika\Mapper\Transformer\Model\TraversableCountableWrapper;

/**
 * @internal
 */
class TraversableToTraversableMappingTest extends FrameworkTestCase
{
    //
    // class property containing array-like
    //

    public function testTraversableToTraversableDto(): void
    {
        $source = new ObjectWithTraversableProperties();

        $result = $this->mapper->map($source, ObjectWithTraversablePropertyDto::class);

        $this->assertInstanceOf(ObjectWithTraversablePropertyDto::class, $result);
        $this->assertNotNull($result->property);
        $this->assertInstanceOf(\Generator::class, $result->property);

        foreach ($result->property as $item) {
            $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $item);

            $this->assertEquals(1, $item->a);
            $this->assertEquals('string', $item->b);
            $this->assertEquals(true, $item->c);
            $this->assertEquals(1.1, $item->d);
        }
    }

    public function testArrayToTraversableDto(): void
    {
        $source = new ObjectWithArrayProperty();

        $result = $this->mapper->map($source, ObjectWithTraversablePropertyDto::class);

        $this->assertInstanceOf(ObjectWithTraversablePropertyDto::class, $result);
        $this->assertNotNull($result->property);
        $this->assertInstanceOf(TraversableCountableWrapper::class, $result->property);

        foreach ($result->property as $item) {
            $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $item);

            $this->assertEquals(1, $item->a);
            $this->assertEquals('string', $item->b);
            $this->assertEquals(true, $item->c);
            $this->assertEquals(1.1, $item->d);
        }
    }

    public function testLazy(): void
    {
        $source = new ObjectWithLazyDoctrineCollectionProperty();

        $result = $this->mapper->map($source, ObjectWithTraversablePropertyDto::class);

        $this->assertInstanceOf(ObjectWithTraversablePropertyDto::class, $result);
        $this->assertNotNull($result->property);
        $this->assertInstanceOf(TraversableCountableWrapper::class, $result->property);

        $this->expectException(\LogicException::class);
        foreach ($result->property as $item) {
            // do nothing
        }
    }

    public function testExtraLazy(): void
    {
        $source = new ObjectWithLazyDoctrineCollectionWithPresetCountableProperty();

        $result = $this->mapper->map($source, ObjectWithTraversablePropertyDto::class);

        $this->assertInstanceOf(ObjectWithTraversablePropertyDto::class, $result);
        $this->assertNotNull($result->property);
        $this->assertInstanceOf(TraversableCountableWrapper::class, $result->property);

        $this->assertEquals(31337, $result->property->count());

        $this->expectException(\LogicException::class);
        foreach ($result->property as $item) {
            // do nothing
        }
    }

    //
    // without type hint
    //

    public function testArrayToTraversableWithoutTypehint(): void
    {
        $source = new ObjectWithArrayProperty();
        $result = $this->mapper->map($source, ObjectWithTraversablePropertyWithoutTypeHintDto::class);

        $this->assertInstanceOf(\Traversable::class, $result->property);
        $this->assertInstanceOf(\Countable::class, $result->property);

        foreach ($result->property as $item) {
            $this->assertInstanceOf(ObjectWithScalarProperties::class, $item);

            $this->assertEquals(1, $item->a);
            $this->assertEquals('string', $item->b);
            $this->assertEquals(true, $item->c);
            $this->assertEquals(1.1, $item->d);
        }
    }

    public function testNullToNotNullTraversableDto(): void
    {
        $source = new ObjectWithNullCollectionProperty();
        $result = $this->mapper->map($source, ObjectWithNotNullTraversablePropertyDto::class);

        $this->assertInstanceOf(\Traversable::class, $result->property);

        $arrayResult = iterator_to_array($result->property);
        $this->assertEmpty($arrayResult);
    }
}
