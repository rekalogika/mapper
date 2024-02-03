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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MainTransformer\MainTransformer;
use Rekalogika\Mapper\PropertyMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\PropertyMapper\PropertyMapperServicePointer;
use Rekalogika\Mapper\Tests\Common\AbstractFrameworkTest;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithClassAttributeWithoutExplicitProperty;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithConstructorWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithExtraArguments;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\PropertyMapperWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectWithConstructorDto;

class PropertyMappingTest extends AbstractFrameworkTest
{
    public function testPropertyMapperRegistration(): void
    {
        $propertyMapperA = $this->get(PropertyMapperWithoutClassAttribute::class);
        $propertyMapperB = $this->get(PropertyMapperWithClassAttribute::class);

        $this->assertInstanceOf(PropertyMapperWithoutClassAttribute::class, $propertyMapperA);
        $this->assertInstanceOf(PropertyMapperWithClassAttribute::class, $propertyMapperB);

        $propertyMapperResolver = $this->get('rekalogika.mapper.property_mapper.resolver');

        $this->assertInstanceOf(PropertyMapperResolverInterface::class, $propertyMapperResolver);
    }

    /**
     * @dataProvider propertyMapperResolverDataProvider
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function testPropertyMapperResolver(
        string $sourceClass,
        string $targetClass,
        string $property,
        ?PropertyMapperServicePointer $expected
    ): void {
        $propertyMapperResolver = $this->get('rekalogika.mapper.property_mapper.resolver');

        $this->assertInstanceOf(PropertyMapperResolverInterface::class, $propertyMapperResolver);

        $result = $propertyMapperResolver->getPropertyMapper($sourceClass, $targetClass, $property);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return iterable<array-key,array{class-string,class-string,string,?PropertyMapperServicePointer}>
     */
    public function propertyMapperResolverDataProvider(): iterable
    {
        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyA',
            new PropertyMapperServicePointer(
                PropertyMapperWithoutClassAttribute::class,
                'mapPropertyA',
                []
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyB',
            new PropertyMapperServicePointer(
                PropertyMapperWithClassAttribute::class,
                'mapPropertyB',
                []
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyC',
            null,
        ];

        yield [
            SomeObject::class,
            SomeObjectWithConstructorDto::class,
            'propertyA',
            new PropertyMapperServicePointer(
                PropertyMapperWithConstructorWithoutClassAttribute::class,
                'mapPropertyA',
                []
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectWithConstructorDto::class,
            'propertyB',
            new PropertyMapperServicePointer(
                PropertyMapperWithConstructorWithClassAttribute::class,
                'mapPropertyB',
                []
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyD',
            new PropertyMapperServicePointer(
                PropertyMapperWithClassAttributeWithoutExplicitProperty::class,
                'mapPropertyD',
                []
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyE',
            new PropertyMapperServicePointer(
                PropertyMapperWithExtraArguments::class,
                'mapPropertyE',
                [
                    PropertyMapperServicePointer::ARGUMENT_CONTEXT,
                    PropertyMapperServicePointer::ARGUMENT_MAIN_TRANSFORMER,
                ]
            ),
        ];
    }

    public function testPropertyMapping(): void
    {
        $object = new SomeObject();
        $dto = $this->mapper->map($object, SomeObjectDto::class);

        $this->assertEquals(SomeObject::class .  '::propertyA', $dto->propertyA);
        $this->assertEquals(SomeObject::class .  '::propertyB', $dto->propertyB);
        $this->assertNull($dto->propertyC);
        $this->assertEquals(SomeObject::class .  '::propertyD', $dto->propertyD);
        $this->assertEquals(sprintf(
            'I have "%s" and "%s" that I can use to transform source property "%s"',
            Context::class,
            MainTransformer::class,
            SomeObject::class,
        ), $dto->propertyE);
    }

    public function testPropertyMappingWithConstructor(): void
    {
        $object = new SomeObject();
        $dto = $this->mapper->map($object, SomeObjectWithConstructorDto::class);

        $this->assertEquals(SomeObject::class .  '::propertyA', $dto->propertyA);
        $this->assertEquals(SomeObject::class .  '::propertyB', $dto->propertyB);
    }
}
