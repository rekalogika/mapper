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

use PHPUnit\Framework\Attributes\RequiresPhp;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\MapperOptions;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ChildObjectWithIdDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithId;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdAndName;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdAndNameInConstructorDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdEagerDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdFinalDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdInConstructorDto;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdReadOnlyDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;

class LazyObjectTest extends FrameworkTestCase
{
    public function testLazyObject(): void
    {
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdDto::class);
        $this->assertSame('id', $target->id);
    }

    public function testLazyObjectHydration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('If lazy, this method must not be called');
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdDto::class);
        $this->initialize($target);
    }

    /**
     * PHP lazy objects support final objects
     */
    #[RequiresPhp('< 8.4')]
    public function testFinal(): void
    {
        // final class can't be lazy
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('If lazy, this method must not be called');
        $source = new ObjectWithId();
        $this->mapper->map($source, ObjectWithIdFinalDto::class);
    }

    public function testEagerAttribute(): void
    {
        // eager-marked class can't be lazy
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('If lazy, this method must not be called');
        $source = new ObjectWithId();
        $this->mapper->map($source, ObjectWithIdEagerDto::class);
    }

    /**
     * In PHP 8.2, readonly class can't be lazy
     */
    #[RequiresPhp('8.2.*')]
    public function testReadOnly82(): void
    {
        // should not use proxy. if a proxy is not used, it should throw an
        // exception
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('If lazy, this method must not be called');
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdReadOnlyDto::class);
    }

    /**
     * In PHP 8.3, readonly class can be lazy
     */
    // public function testReadOnly83(): void
    // {
    //     $source = new ObjectWithId();
    //     $target = $this->mapper->map($source, ObjectWithIdReadOnlyDto::class);
    // }
    #[RequiresPhp('>= 8.3.0')]
    public function testIdInParentClass(): void
    {
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ChildObjectWithIdDto::class);
        $this->assertSame('id', $target->id);
    }

    public function testIdInParentClassInitialized(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('If lazy, this method must not be called');

        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ChildObjectWithIdDto::class);
        $foo = $target->name;
    }

    /**
     * If the constructor has an eager property, the constructor is eager.
     */
    public function testEagerAndLazyPropertyInConstruct(): void
    {
        $source = new ObjectWithIdAndName();
        $target = $this->mapper->map($source, ObjectWithIdAndNameInConstructorDto::class);
        $this->assertIsUninitializedProxy($target);

        $this->assertTrue($source->isIdAndNameCalled());
        $this->assertIsUninitializedProxy($target);

        $this->assertEquals('other', $target->other);
        $this->assertNotUninitializedProxy($target);
    }

    /**
     * If the constructor has only lazy properties, the constructor is lazy.
     */
    public function testLazyPropertyOnlyInConstruct(): void
    {
        $source = new ObjectWithIdAndName();
        $target = $this->mapper->map($source, ObjectWithIdInConstructorDto::class);
        $this->assertIsUninitializedProxy($target);

        $this->assertTrue($source->isIdCalled());
        $this->assertFalse($source->isNameCalled());
        $this->assertIsUninitializedProxy($target);

        $name = $target->name;
        $this->assertNotUninitializedProxy($target);
        $this->assertTrue($source->isNameCalled());
        $this->assertEquals('name', $name);
    }

    public function testEnablingLazyObject(): void
    {
        $options = new MapperOptions(lazyLoading: true);
        $context = Context::create($options);

        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectWithScalarPropertiesDto::class, $context);
        $this->assertIsUninitializedProxy($target);
    }

    public function testDisablingLazyObject(): void
    {
        $options = new MapperOptions(lazyLoading: false);
        $context = Context::create($options);

        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectWithScalarPropertiesDto::class, $context);
        $this->assertNotUninitializedProxy($target);
    }
}
