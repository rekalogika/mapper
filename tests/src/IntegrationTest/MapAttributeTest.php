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
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\MapAttribute\SomeObjectDto;

class MapAttributeTest extends FrameworkTestCase
{
    public function testMapAttribute(): void
    {
        $source = new SomeObject();
        $target = $this->mapper->map($source, SomeObjectDto::class);

        $this->assertEquals('propertyA', $target->targetPropertyA);
        $this->assertEquals('propertyB', $target->getTargetPropertyB());
        $this->assertEquals('propertyC', $target->getTargetPropertyC());
    }
}
