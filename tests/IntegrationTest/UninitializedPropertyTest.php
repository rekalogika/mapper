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

use Rekalogika\Mapper\Tests\Common\AbstractFrameworkTest;
use Rekalogika\Mapper\Tests\Fixtures\UninitializedProperty\ObjectWithInitializedProperty;
use Rekalogika\Mapper\Tests\Fixtures\UninitializedProperty\ObjectWithUninitializedProperty;
use Rekalogika\Mapper\Tests\Fixtures\UninitializedPropertyDto\FinalObjectWithInitializedPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\UninitializedPropertyDto\FinalObjectWithUninitializedPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\UninitializedPropertyDto\ObjectWithInitializedPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\UninitializedPropertyDto\ObjectWithUninitializedPropertyDto;

class UninitializedPropertyTest extends AbstractFrameworkTest
{
    // from initialized

    public function testInitializedToInitialized(): void
    {
        $object = new ObjectWithInitializedProperty();
        $dto = $this->mapper->map($object, ObjectWithInitializedPropertyDto::class);
        $this->initialize($dto);
        $this->assertSame('foo', $dto->property->name);
    }

    public function testInitializedToFinalInitialized(): void
    {
        $object = new ObjectWithInitializedProperty();
        $dto = $this->mapper->map($object, FinalObjectWithInitializedPropertyDto::class);
        $this->initialize($dto);
        $this->assertSame('foo', $dto->property->name);
    }

    public function testInitializedToUnitialized(): void
    {
        $object = new ObjectWithInitializedProperty();
        $dto = $this->mapper->map($object, ObjectWithUninitializedPropertyDto::class);
        $this->initialize($dto);
        $this->assertSame('foo', $dto->property->name);
    }

    public function testInitializedToFinalUnitialized(): void
    {
        $object = new ObjectWithInitializedProperty();
        $dto = $this->mapper->map($object, FinalObjectWithUninitializedPropertyDto::class);
        $this->initialize($dto);
        $this->assertSame('foo', $dto->property->name);
    }

    // from uninitialized

    public function testUnitializedToInitialized(): void
    {
        $object = new ObjectWithUninitializedProperty();
        $dto = $this->mapper->map($object, ObjectWithInitializedPropertyDto::class);
        $this->initialize($dto);
        $this->assertSame('bar', $dto->property->name);
    }

    public function testUninitializedToFinalInitialized(): void
    {
        $object = new ObjectWithUninitializedProperty();
        $dto = $this->mapper->map($object, FinalObjectWithInitializedPropertyDto::class);
        $this->initialize($dto);
        $this->assertSame('bar', $dto->property->name);
    }

    public function testUninitializedToUnitialized(): void
    {
        $this->expectException(\Error::class);
        $object = new ObjectWithUninitializedProperty();
        $dto = $this->mapper->map($object, ObjectWithUninitializedPropertyDto::class);
        $this->initialize($dto);
        $this->assertSame('foo', $dto->property->name);
    }

    public function testUninitializedToFinalUnitialized(): void
    {
        $this->expectException(\Error::class);
        $object = new ObjectWithUninitializedProperty();
        $dto = $this->mapper->map($object, FinalObjectWithUninitializedPropertyDto::class);
        $this->initialize($dto);
        $this->assertSame('foo', $dto->property->name);
    }
}
