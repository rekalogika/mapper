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

use PHPUnit\Framework\Attributes\DataProvider;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\DynamicProperty\AnotherObjectExtendingStdClass;
use Rekalogika\Mapper\Tests\Fixtures\DynamicProperty\ObjectExtendingStdClass;
use Rekalogika\Mapper\Tests\Fixtures\DynamicProperty\ObjectExtendingStdClassWithExplicitScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\DynamicProperty\ObjectExtendingStdClassWithProperties;
use Rekalogika\Mapper\Tests\Fixtures\DynamicProperty\ObjectWithNonNullPropertyThatCannotBeCastFromNull;
use Rekalogika\Mapper\Tests\Fixtures\DynamicProperty\ObjectWithNullProperty;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;
use Rekalogika\Mapper\Transformer\MetadataUtil\DynamicPropertiesDeterminer\DynamicPropertiesDeterminer;

class DynamicPropertyTest extends FrameworkTestCase
{
    /**
     * @param class-string $class
     */
    #[DataProvider('provideDynamicPropertiesDetermination')]
    public function testDynamicPropertiesDetermination(
        string $class,
        bool $expected,
    ): void {
        $determiner = new DynamicPropertiesDeterminer();
        $actual = $determiner->allowsDynamicProperties($class);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return iterable<array-key,array{class-string,bool}>
     */
    public static function provideDynamicPropertiesDetermination(): iterable
    {
        yield 'stdClass' => [
            \stdClass::class,
            true,
        ];

        yield 'ObjectExtendingStdClass' => [
            ObjectExtendingStdClass::class,
            true,
        ];

        yield 'ObjectWithScalarProperties' => [
            ObjectWithScalarProperties::class,
            false,
        ];

        yield 'ObjectWithScalarPropertiesDto' => [
            ObjectWithScalarPropertiesDto::class,
            false,
        ];

        yield 'AnotherObjectExtendingStdClass' => [
            AnotherObjectExtendingStdClass::class,
            true,
        ];

        yield 'ObjectExtendingStdClassWithExplicitScalarProperties' => [
            ObjectExtendingStdClassWithExplicitScalarProperties::class,
            true,
        ];

        yield 'ObjectExtendingStdClassWithProperties' => [
            ObjectExtendingStdClassWithProperties::class,
            true,
        ];

        yield 'ObjectWithNonNullPropertyThatCannotBeCastFromNull' => [
            ObjectWithNonNullPropertyThatCannotBeCastFromNull::class,
            false,
        ];
    }

    // from stdclass to object

    public function testStdClassToObject(): void
    {
        $source = new \stdClass();
        $source->a = 1;
        $source->b = 'string';
        $source->c = true;
        $source->d = 1.1;

        $target = $this->mapper->map($source, ObjectWithScalarPropertiesDto::class);

        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target);
        $this->assertEquals(1, $target->a);
        $this->assertEquals('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertEquals(1.1, $target->d);
    }

    public function testObjectExtendingStdClassToObject(): void
    {
        $source = new ObjectExtendingStdClass();
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->a = 1;
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->b = 'string';
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->c = true;
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->d = 1.1;

        $target = $this->mapper->map($source, ObjectWithScalarPropertiesDto::class);

        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target);
        $this->assertEquals(1, $target->a);
        $this->assertEquals('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertEquals(1.1, $target->d);
    }

    public function testArrayCastToObjectToObject(): void
    {
        $source = [
            'a' => 1,
            'b' => 'string',
            'c' => true,
            'd' => 1.1,
        ];

        $target = $this->mapper->map((object) $source, ObjectWithScalarPropertiesDto::class);

        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target);
        $this->assertEquals(1, $target->a);
        $this->assertEquals('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertEquals(1.1, $target->d);
    }

    public function testStdClassWithoutPropertiesToObject(): void
    {
        $source = new \stdClass();
        $target = $this->mapper->map($source, ObjectWithScalarPropertiesDto::class);

        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target);
        $this->assertNull($target->a);
        $this->assertNull($target->b);
        $this->assertNull($target->c);
        $this->assertNull($target->d);
    }

    public function testStdClassWithExtraPropertyToObject(): void
    {
        $source = new \stdClass();
        $source->a = 1;
        $source->b = 'string';
        $source->c = true;
        $source->d = 1.1;
        $source->e = 'extra';

        $target = $this->mapper->map($source, ObjectWithScalarPropertiesDto::class);

        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target);
        $this->assertEquals(1, $target->a);
        $this->assertEquals('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertEquals(1.1, $target->d);
    }

    public function testObjectExtendingStdClassWithExplicitScalarPropertiesToObject(): void
    {
        $source = new ObjectExtendingStdClassWithExplicitScalarProperties();
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->e = 'extra';

        $target = $this->mapper->map($source, ObjectWithScalarPropertiesDto::class);

        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target);
        $this->assertEquals(1, $target->a);
        $this->assertEquals('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertEquals(1.1, $target->d);
    }

    // to stdClass

    public function testObjectToStdClass(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $target);

        $this->assertEquals(1, $target->a);
        $this->assertEquals('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertEquals(1.1, $target->d);
    }

    public function testObjectToObjectExtendingStdClass(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectExtendingStdClass::class);

        $this->assertInstanceOf(ObjectExtendingStdClass::class, $target);

        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertEquals(1, $target->a);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertEquals('string', $target->b);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertTrue($target->c);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertEquals(1.1, $target->d);
    }

    // stdclass to stdclass

    public function testStdClassToStdClassasdf(): void
    {
        $source = new ObjectExtendingStdClass();
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->a = 1;
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->b = 'string';
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->c = true;
        /** @psalm-suppress UndefinedPropertyAssignment */
        $source->d = 1.1;

        $target = $this->mapper->map($source, AnotherObjectExtendingStdClass::class);

        $this->assertInstanceOf(\stdClass::class, $target);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertEquals(1, $target->a);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertEquals('string', $target->b);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertTrue($target->c);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertEquals(1.1, $target->d);
    }

    public function testStdClassToStdClassWithExplicitProperties(): void
    {
        $source = new \stdClass();
        $source->public = 'public';
        // in the new behavior, this will fail, because there is no setter on
        // the target side
        // $source->private = 'private';
        $source->constructor = 'constructor';
        $source->dynamic = 'dynamic';

        $target = $this->mapper->map($source, ObjectExtendingStdClassWithProperties::class);

        $this->assertInstanceOf(\stdClass::class, $target);
        $this->assertEquals('public', $target->public);
        $this->assertNull($target->getPrivate());
        $this->assertEquals('constructor', $target->getConstructor());
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertEquals('dynamic', $target->dynamic);
    }

    public function testStdClassToStdClassWithExistingValue(): void
    {
        $source = new \stdClass();
        $source->property = new ObjectWithScalarProperties();

        $target = new \stdClass();
        $targetProperty = new ObjectWithScalarPropertiesDto();
        $target->property = $targetProperty;

        $this->mapper->map($source, $target);

        $this->assertSame($targetProperty, $target->property);
    }

    public function testStdClassToStdClassWithExistingNullValue(): void
    {
        $source = new \stdClass();
        $source->property = new ObjectWithScalarProperties();

        $target = new \stdClass();
        $target->property = null;

        $this->mapper->map($source, $target);

        /**
         * @psalm-suppress TypeDoesNotContainType
         * @phpstan-ignore-next-line
         */
        $this->assertInstanceOf(ObjectWithScalarProperties::class, $target->property);
    }

    public function testStdClassToObjectWithNotNullProperty(): void
    {
        $source = new ObjectExtendingStdClass();
        $result = $this->mapper->map($source, ObjectWithNonNullPropertyThatCannotBeCastFromNull::class);

        $this->assertFalse(isset($result->date));
    }

    public function testNullToStdClass(): void
    {
        $source = new ObjectWithNullProperty();
        $target = $this->mapper->map($source, \stdClass::class);

        $this->assertNull($target->property);
    }
}
