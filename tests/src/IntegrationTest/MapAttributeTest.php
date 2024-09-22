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
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\ObjectExtendingSomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\ObjectOverridingSomeObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObjectDto;

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
}
