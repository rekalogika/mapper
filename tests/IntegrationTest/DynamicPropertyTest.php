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
use Rekalogika\Mapper\Tests\Fixtures\DynamicProperty\ObjectExtendingStdClass;
use Rekalogika\Mapper\Tests\Fixtures\DynamicProperty\ObjectExtendingStdClassWithProperties;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;

class DynamicPropertyTest extends FrameworkTestCase
{
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
        $this->assertSame(1, $target->a);
        $this->assertSame('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertSame(1.1, $target->d);
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
        $this->assertSame(1, $target->a);
        $this->assertSame('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertSame(1.1, $target->d);
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
        $this->assertSame(1, $target->a);
        $this->assertSame('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertSame(1.1, $target->d);
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

    // to stdClass

    public function testObjectToStdClass(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $target);

        $this->assertSame(1, $target->a);
        $this->assertSame('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertSame(1.1, $target->d);
    }

    public function testObjectToObjectExtendingStdClass(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectExtendingStdClass::class);

        $this->assertInstanceOf(ObjectExtendingStdClass::class, $target);

        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame(1, $target->a);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame('string', $target->b);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertTrue($target->c);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame(1.1, $target->d);
    }

    // stdclass to stdclass

    public function testStdClassToStdClass(): void
    {
        $source = new \stdClass();
        $source->a = 1;
        $source->b = 'string';
        $source->c = true;
        $source->d = 1.1;

        $target = $this->mapper->map($source, \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $target);
        $this->assertSame(1, $target->a);
        $this->assertSame('string', $target->b);
        $this->assertTrue($target->c);
        $this->assertSame(1.1, $target->d);
    }

    public function testStdClassToStdClassWithExplicitProperties(): void
    {
        $source = new \stdClass();
        $source->public = 'public';
        $source->private = 'private';
        $source->constructor = 'constructor';

        $target = $this->mapper->map($source, ObjectExtendingStdClassWithProperties::class);

        $this->assertInstanceOf(\stdClass::class, $target);
        $this->assertSame('public', $target->public);
        $this->assertNull($target->getPrivate());
        $this->assertEquals('constructor', $target->getConstructor());
    }
}
