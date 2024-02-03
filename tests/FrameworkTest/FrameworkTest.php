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

namespace Rekalogika\Mapper\Tests\FrameworkTest;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\PropertyMapper\Contracts\PropertyMapperResolverInterface;
use Rekalogika\Mapper\PropertyMapper\Contracts\PropertyMapperServicePointer;
use Rekalogika\Mapper\Tests\Common\TestKernel;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectWithConstructorDto;

class FrameworkTest extends TestCase
{
    private ?ContainerInterface $container = null;

    public function setUp(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();
        $this->container = $kernel->getContainer();
    }

    public function testWiring(): void
    {

        foreach (TestKernel::getServiceIds() as $serviceId) {
            $service = $this->container?->get('test.' . $serviceId);

            $this->assertIsObject($service);
        }
    }

    public function testPropertyMapperRegistration(): void
    {
        $propertyMapperA = $this->container?->get(PropertyMapperWithoutClassAttribute::class);
        $propertyMapperB = $this->container?->get(PropertyMapperWithClassAttribute::class);

        $this->assertInstanceOf(PropertyMapperWithoutClassAttribute::class, $propertyMapperA);
        $this->assertInstanceOf(PropertyMapperWithClassAttribute::class, $propertyMapperB);

        $propertyMapperResolver = $this->container?->get('test.rekalogika.mapper.property_mapper.resolver');

        $this->assertInstanceOf(PropertyMapperResolverInterface::class, $propertyMapperResolver);

        $result1 = $propertyMapperResolver->getPropertyMapper(
            SomeObject::class,
            SomeObjectDto::class,
            'propertyA'
        );

        $this->assertEquals(
            new PropertyMapperServicePointer(
                PropertyMapperWithoutClassAttribute::class,
                'mapPropertyA'
            ),
            $result1,
        );

        $result2 = $propertyMapperResolver->getPropertyMapper(
            SomeObject::class,
            SomeObjectDto::class,
            'propertyB'
        );

        $this->assertEquals(
            new PropertyMapperServicePointer(
                PropertyMapperWithClassAttribute::class,
                'mapPropertyB'
            ),
            $result2,
        );

        $result3 = $propertyMapperResolver->getPropertyMapper(
            SomeObject::class,
            SomeObjectDto::class,
            'propertyC'
        );

        $this->assertNull($result3);

        $result4 = $propertyMapperResolver->getPropertyMapper(
            SomeObject::class,
            SomeObjectWithConstructorDto::class,
            'propertyA'
        );

        $this->assertEquals(
            new PropertyMapperServicePointer(
                PropertyMapperWithConstructorWithoutClassAttribute::class,
                'mapPropertyA'
            ),
            $result4,
        );

        $result5 = $propertyMapperResolver->getPropertyMapper(
            SomeObject::class,
            SomeObjectWithConstructorDto::class,
            'propertyB'
        );

        $this->assertEquals(
            new PropertyMapperServicePointer(
                PropertyMapperWithConstructorWithClassAttribute::class,
                'mapPropertyB'
            ),
            $result5,
        );
    }
}
