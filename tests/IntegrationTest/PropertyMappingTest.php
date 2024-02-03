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
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectWithConstructorDto;

class PropertyMappingTest extends AbstractIntegrationTest
{
    protected function getPropertyMappers(): iterable
    {
        yield [
            'sourceClass' => SomeObject::class,
            'targetClass' => SomeObjectDto::class,
            'property' => 'propertyA',
            'service' => new PropertyMapperWithoutClassAttribute(),
            'method' => 'mapPropertyA',
        ];

        yield [
            'sourceClass' => SomeObject::class,
            'targetClass' => SomeObjectDto::class,
            'property' => 'propertyB',
            'service' => new PropertyMapperWithClassAttribute(),
            'method' => 'mapPropertyB',
        ];

        yield [
            'sourceClass' => SomeObject::class,
            'targetClass' => SomeObjectWithConstructorDto::class,
            'property' => 'propertyA',
            'service' => new PropertyMapperWithConstructorWithoutClassAttribute(),
            'method' => 'mapPropertyA',
        ];

        yield [
            'sourceClass' => SomeObject::class,
            'targetClass' => SomeObjectWithConstructorDto::class,
            'property' => 'propertyB',
            'service' => new PropertyMapperWithConstructorWithClassAttribute(),
            'method' => 'mapPropertyB',
        ];
    }

    public function testPropertyMapping(): void
    {
        $object = new SomeObject();
        $dto = $this->mapper->map($object, SomeObjectDto::class);

        $this->assertEquals(SomeObject::class .  '::propertyA', $dto->propertyA);
        $this->assertEquals(SomeObject::class .  '::propertyB', $dto->propertyB);
    }

    public function testPropertyMappingWithConstructor(): void
    {
        $object = new SomeObject();
        $dto = $this->mapper->map($object, SomeObjectWithConstructorDto::class);

        $this->assertEquals(SomeObject::class .  '::propertyA', $dto->propertyA);
        $this->assertEquals(SomeObject::class .  '::propertyB', $dto->propertyB);
    }
}
