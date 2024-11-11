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
use Rekalogika\Mapper\Tests\Fixtures\Array\ObjectWithList;
use Rekalogika\Mapper\Tests\Fixtures\Array\ObjectWithListWithAllowDelete;

class ArrayToArrayTest extends FrameworkTestCase
{
    public function testListToListWithAllowDelete(): void
    {
        $target = new ObjectWithListWithAllowDelete();
        $target->array = ['foo', 'bar'];

        $source = new ObjectWithListWithAllowDelete();
        $source->array = ['baz'];

        $this->mapper->map($source, $target);

        $this->assertEquals(['baz'], $target->array);
    }

    public function testListToListWithoutAllowDelete(): void
    {
        $target = new ObjectWithList();
        $target->array = ['foo', 'bar'];

        $source = new ObjectWithList();
        $source->array = ['baz'];

        $this->mapper->map($source, $target);

        $this->assertEquals(['foo', 'bar', 'baz'], $target->array);
    }
}
