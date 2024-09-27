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
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ParentObjectWithoutSetterDto;
use Rekalogika\Mapper\Transformer\Exception\NewInstanceReturnedButCannotBeSetOnTargetException;

class WitherMethodTest extends FrameworkTestCase
{
    public function testImmutableSetter(): void
    {
        $source = new ParentObject();
        $target = new ParentObjectDto();

        $originalObject = $target->getObject();
        $result = $this->mapper->map($source, $target);
        $resultObject = $result->getObject();

        $this->assertNotSame($originalObject, $resultObject);
    }

    public function testChildImmutableSetterWithoutSetterOnParent(): void
    {
        $this->expectException(NewInstanceReturnedButCannotBeSetOnTargetException::class);

        $source = new ParentObject();
        $target = new ParentObjectWithoutSetterDto();
        $this->mapper->map($source, $target);
    }
}
