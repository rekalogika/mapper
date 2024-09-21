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
use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\MainTransformer\Implementation\MainTransformer;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\Bar;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\Baz;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\ChildOfSomeObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\ChildOfSomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\Foo;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectWithConstructorDto;
use Rekalogika\Mapper\Tests\Services\PropertyMapper\PropertyMapperWithClassAttribute;
use Rekalogika\Mapper\Tests\Services\PropertyMapper\PropertyMapperWithClassAttributeWithoutExplicitProperty;
use Rekalogika\Mapper\Tests\Services\PropertyMapper\PropertyMapperWithConstructorWithClassAttribute;
use Rekalogika\Mapper\Tests\Services\PropertyMapper\PropertyMapperWithConstructorWithoutClassAttribute;
use Rekalogika\Mapper\Tests\Services\PropertyMapper\PropertyMapperWithExtraArguments;
use Rekalogika\Mapper\Tests\Services\PropertyMapper\PropertyMapperWithoutClassAttribute;

class PropertyMappingTest extends FrameworkTestCase
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
        ?ServiceMethodSpecification $expected,
    ): void {
        $propertyMapperResolver = $this->get('rekalogika.mapper.property_mapper.resolver');

        $this->assertInstanceOf(PropertyMapperResolverInterface::class, $propertyMapperResolver);

        $result = $propertyMapperResolver->getPropertyMapper($sourceClass, $targetClass, $property);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return iterable<array-key,array{class-string,class-string,string,?ServiceMethodSpecification}>
     */
    public static function propertyMapperResolverDataProvider(): iterable
    {
        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyA',
            new ServiceMethodSpecification(
                PropertyMapperWithoutClassAttribute::class,
                'mapPropertyA',
                [],
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyB',
            new ServiceMethodSpecification(
                PropertyMapperWithClassAttribute::class,
                'mapPropertyB',
                [],
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
            new ServiceMethodSpecification(
                PropertyMapperWithConstructorWithoutClassAttribute::class,
                'mapPropertyA',
                [],
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectWithConstructorDto::class,
            'propertyB',
            new ServiceMethodSpecification(
                PropertyMapperWithConstructorWithClassAttribute::class,
                'mapPropertyB',
                [],
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyD',
            new ServiceMethodSpecification(
                PropertyMapperWithClassAttributeWithoutExplicitProperty::class,
                'mapPropertyD',
                [],
            ),
        ];

        yield [
            SomeObject::class,
            SomeObjectDto::class,
            'propertyE',
            new ServiceMethodSpecification(
                PropertyMapperWithExtraArguments::class,
                'mapPropertyE',
                [
                    ServiceMethodSpecification::ARGUMENT_CONTEXT,
                    ServiceMethodSpecification::ARGUMENT_MAIN_TRANSFORMER,
                ],
            ),
        ];
    }

    public function testPropertyMapping(): void
    {
        $object = new SomeObject();
        $dto = $this->mapper->map($object, SomeObjectDto::class);

        $this->assertEquals(SomeObject::class . '::propertyA', $dto->propertyA);
        $this->assertEquals(SomeObject::class . '::propertyB', $dto->propertyB);
        $this->assertNull($dto->propertyC);
        $this->assertEquals(SomeObject::class . '::propertyD', $dto->propertyD);
        $this->assertEquals(\sprintf(
            'I have "%s" and "%s" that I can use to transform source property "%s"',
            Context::class,
            MainTransformer::class,
            SomeObject::class,
        ), $dto->propertyE);
    }

    public function testPropertyMappingChildToParent(): void
    {
        $object = new ChildOfSomeObject();
        $dto = $this->mapper->map($object, SomeObjectDto::class);

        $this->assertEquals(ChildOfSomeObject::class . '::propertyA', $dto->propertyA);
        $this->assertEquals(ChildOfSomeObject::class . '::propertyB', $dto->propertyB);
        $this->assertNull($dto->propertyC);
        $this->assertEquals(ChildOfSomeObject::class . '::propertyD', $dto->propertyD);
        $this->assertEquals(\sprintf(
            'I have "%s" and "%s" that I can use to transform source property "%s"',
            Context::class,
            MainTransformer::class,
            ChildOfSomeObject::class,
        ), $dto->propertyE);
    }

    public function testPropertyMappingChildToChild(): void
    {
        $object = new ChildOfSomeObject();
        $dto = $this->mapper->map($object, ChildOfSomeObjectDto::class);

        $this->assertEquals(ChildOfSomeObject::class . '::propertyA', $dto->propertyA);
        $this->assertEquals(ChildOfSomeObject::class . '::propertyB', $dto->propertyB);
        $this->assertNull($dto->propertyC);
        $this->assertEquals(ChildOfSomeObject::class . '::propertyD', $dto->propertyD);
        $this->assertEquals(\sprintf(
            'I have "%s" and "%s" that I can use to transform source property "%s"',
            Context::class,
            MainTransformer::class,
            ChildOfSomeObject::class,
        ), $dto->propertyE);
    }

    public function testPropertyMappingParentToChild(): void
    {
        $object = new SomeObject();
        $dto = $this->mapper->map($object, ChildOfSomeObjectDto::class);

        $this->assertEquals(SomeObject::class . '::propertyA', $dto->propertyA);
        $this->assertEquals(SomeObject::class . '::propertyB', $dto->propertyB);
        $this->assertNull($dto->propertyC);
        $this->assertEquals(SomeObject::class . '::propertyD', $dto->propertyD);
        $this->assertEquals(\sprintf(
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

        $this->assertEquals(SomeObject::class . '::propertyA', $dto->propertyA);
        $this->assertEquals(SomeObject::class . '::propertyB', $dto->propertyB);
    }

    public function testUnionTypes(): void
    {
        $baz1 = $this->mapper->map(new Foo(), Baz::class);
        $this->assertEquals('Foo', $baz1->bazName);

        $baz2 = $this->mapper->map(new Bar(), Baz::class);
        $this->assertEquals('Bar', $baz2->bazName);
    }
}
