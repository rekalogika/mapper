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
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\ObjectExtendingOtherObject;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\ObjectExtendingSomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\ObjectOverridingSomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\OtherObject;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObjectSkippingMapping;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObjectSkippingMappingDto;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObjectWithInvalidTargetDto;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObjectWithSamePropertyNameDto;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObjectWithUnpromotedConstructorDto;
use Rekalogika\Mapper\Transformer\Exception\PairedPropertyNotFoundException;

class MapAttributeTest extends FrameworkTestCase
{
    public function testMapAttribute(): void
    {
        $source = SomeObject::preinitialized();
        $target = $this->mapper->map($source, SomeObjectDto::class);

        $this->assertEquals('sourcePropertyA', $target->targetPropertyA);
        $this->assertEquals('sourcePropertyB', $target->getTargetPropertyB());
        $this->assertEquals('sourcePropertyC', $target->getTargetPropertyC());
    }

    public function testReverseMapAttribute(): void
    {
        $source = SomeObjectDto::preinitialized();
        $target = $this->mapper->map($source, SomeObject::class);

        $this->assertEquals('targetPropertyA', $target->sourcePropertyA);
        $this->assertEquals('targetPropertyB', $target->sourcePropertyB);
        $this->assertEquals('targetPropertyC', $target->sourcePropertyC);
    }

    public function testMapAttributeOnSubclass(): void
    {
        $source = SomeObject::preinitialized();
        $target = $this->mapper->map($source, ObjectExtendingSomeObjectDto::class);

        $this->assertEquals('sourcePropertyA', $target->targetPropertyA);
        $this->assertEquals('sourcePropertyB', $target->getTargetPropertyB());
        $this->assertEquals('sourcePropertyC', $target->getTargetPropertyC());
    }

    public function testReverseMapAttributeOnSubclass(): void
    {
        $source = ObjectExtendingSomeObjectDto::preinitialized();
        $target = $this->mapper->map($source, SomeObject::class);

        $this->assertEquals('targetPropertyA', $target->sourcePropertyA);
        $this->assertEquals('targetPropertyB', $target->sourcePropertyB);
        $this->assertEquals('targetPropertyC', $target->sourcePropertyC);
    }

    public function testMapAttributeOnOverridingSubclass(): void
    {
        $source = SomeObject::preinitialized();
        $target = $this->mapper->map($source, ObjectOverridingSomeObjectDto::class);

        $this->assertEquals('sourcePropertyB', $target->targetPropertyA);
        $this->assertEquals('sourcePropertyC', $target->getTargetPropertyB());
        $this->assertEquals('sourcePropertyA', $target->getTargetPropertyC());
    }

    public function testReverseMapAttributeOnOverridingSubclass(): void
    {
        $source = ObjectOverridingSomeObjectDto::preinitialized();
        $target = $this->mapper->map($source, SomeObject::class);

        $this->assertEquals('targetPropertyA', $target->sourcePropertyA);
        $this->assertEquals('targetPropertyB', $target->sourcePropertyB);
        $this->assertEquals('targetPropertyC', $target->sourcePropertyC);
    }

    public function testMapWithClassProperty(): void
    {
        $source = OtherObject::preinitialized();
        $target = $this->mapper->map($source, SomeObjectDto::class);

        $this->assertEquals('otherSourcePropertyA', $target->targetPropertyA);
        $this->assertEquals('otherSourcePropertyB', $target->getTargetPropertyB());
        $this->assertEquals('otherSourcePropertyC', $target->getTargetPropertyC());
    }

    public function testReverseMapWithClassProperty(): void
    {
        $source = SomeObjectDto::preinitialized();
        $target = $this->mapper->map($source, OtherObject::class);

        $this->assertEquals('targetPropertyA', $target->otherSourcePropertyA);
        $this->assertEquals('targetPropertyB', $target->otherSourcePropertyB);
        $this->assertEquals('targetPropertyC', $target->otherSourcePropertyC);
    }

    public function testMapWithClassPropertyInvolvingSubclass(): void
    {
        $source = ObjectExtendingOtherObject::preinitialized();
        $target = $this->mapper->map($source, SomeObjectDto::class);

        $this->assertEquals('otherSourcePropertyA', $target->targetPropertyA);
        $this->assertEquals('otherSourcePropertyB', $target->getTargetPropertyB());
        $this->assertEquals('otherSourcePropertyC', $target->getTargetPropertyC());
    }

    public function testReverseMapWithClassPropertyInvolvingSubclass(): void
    {
        $source = SomeObjectDto::preinitialized();
        $target = $this->mapper->map($source, ObjectExtendingOtherObject::class);

        $this->assertEquals('targetPropertyA', $target->otherSourcePropertyA);
        $this->assertEquals('targetPropertyB', $target->otherSourcePropertyB);
        $this->assertEquals('targetPropertyC', $target->otherSourcePropertyC);
    }

    public function testMapAttributeToUnpromotedConstructorParameter(): void
    {
        $source = SomeObject::preinitialized();
        $target = $this->mapper->map($source, SomeObjectWithUnpromotedConstructorDto::class);

        $this->assertEquals('sourcePropertyA', $target->targetPropertyA);
    }

    public function testMapAttributeWithInvalidProperty(): void
    {
        $this->expectException(PairedPropertyNotFoundException::class);
        $source = SomeObject::preinitialized();
        $target = $this->mapper->map($source, SomeObjectWithInvalidTargetDto::class);

    }

    public function testToSameProperty(): void
    {
        $source = SomeObject::preinitialized();
        $target = $this->mapper->map($source, SomeObjectWithSamePropertyNameDto::class);

        $this->assertEquals('sourcePropertyA', $target->sourcePropertyA);
        $this->assertEquals('sourcePropertyB', $target->sourcePropertyB);
        $this->assertEquals('sourcePropertyC', $target->sourcePropertyC);
    }

    public function testToSamePropertyButUnmapped(): void
    {
        $source = SomeObject::preinitialized();
        $target = $this->mapper->map($source, SomeObjectSkippingMappingDto::class);

        $this->assertNull($target->sourcePropertyA);
        $this->assertNull($target->sourcePropertyB);
        $this->assertNull($target->sourcePropertyC);
    }

    public function testIgnoringFromSourceSide(): void
    {
        $source = SomeObjectSkippingMapping::preinitialized();
        $target = $this->mapper->map($source, SomeObjectWithSamePropertyNameDto::class);

        $this->assertNull($target->sourcePropertyA);
        $this->assertNull($target->sourcePropertyB);
        $this->assertNull($target->sourcePropertyC);
    }
}
