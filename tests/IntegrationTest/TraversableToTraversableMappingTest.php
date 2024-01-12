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

use Rekalogika\Mapper\Model\TraversableCountableWrapper;
use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithLazyDoctrineCollectionProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithLazyDoctrineCollectionWithPresetCountableProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithTraversableProperties;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithTraversablePropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesDto;

class TraversableToTraversableMappingTest extends AbstractIntegrationTest
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
            $this->assertEquals("string", $item->b);
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
            $this->assertEquals("string", $item->b);
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
        foreach ($result->property as $item);
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
        foreach ($result->property as $item);
    }
}
