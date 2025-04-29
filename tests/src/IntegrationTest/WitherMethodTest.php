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
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObject;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObjectWithObjectWithFluentSetter as ParentObjectWithObjectWithFluentSetterDto;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObjectWithObjectWithImmutableSetter;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObjectWithObjectWithSetterReturningForeignObject;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObjectWithObjectWithVoidSetter as ParentObjectWithObjectWithVoidSetterDto;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObjectWithObjectWithWither;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObjectWithoutSetterDto;

class WitherMethodTest extends FrameworkTestCase
{
    public function testFluentSetter(): void
    {
        $source = new ParentObject();
        $target = new ParentObjectWithObjectWithFluentSetterDto();

        $originalObject = $target->getObject();
        $result = $this->mapper->map($source, $target);
        $resultObject = $result->getObject();

        $this->assertSame($originalObject, $resultObject);
    }

    public function testVoidSetter(): void
    {
        $source = new ParentObject();
        $target = new ParentObjectWithObjectWithVoidSetterDto();

        $originalObject = $target->getObject();
        $result = $this->mapper->map($source, $target);
        $resultObject = $result->getObject();

        $this->assertSame($originalObject, $resultObject);
    }

    public function testSetterReturningForeignObject(): void
    {
        $source = new ParentObject();
        $target = new ParentObjectWithObjectWithSetterReturningForeignObject();

        $originalObject = $target->getObject();
        $result = $this->mapper->map($source, $target);
        $resultObject = $result->getObject();

        $this->assertSame($originalObject, $resultObject);
    }

    public function testImmutableSetter(): void
    {
        $source = new ParentObject();
        $target = new ParentObjectWithObjectWithImmutableSetter();

        $originalObject = $target->getObject();
        $result = $this->mapper->map($source, $target);
        $resultObject = $result->getObject();

        $this->assertNotSame($originalObject, $resultObject);
    }

    public function testWither(): void
    {
        $source = new ParentObject();
        $target = new ParentObjectWithObjectWithWither();

        $originalObject = $target->getObject();
        $result = $this->mapper->map($source, $target);
        $resultObject = $result->getObject();

        $this->assertNotSame($originalObject, $resultObject);
    }

    public function testChildImmutableSetterWithoutSetterOnParent(): void
    {
        $source = new ParentObject();
        $target = new ParentObjectWithoutSetterDto();
        $this->mapper->map($source, $target);

        $this->assertLogContains('results in a different object instance from the original instance');
    }

    public function testImmutableSetterWithProxy(): void
    {
        $source = new ParentObject();
        $result = $this->mapper->map($source, ParentObjectWithObjectWithImmutableSetter::class);

        $this->assertIsUninitializedProxy($result);
        $this->assertInstanceOf(ParentObjectWithObjectWithImmutableSetter::class, $result);
        $this->assertSame($source->getObject()->property, $result->getObject()->getProperty());
    }
}
