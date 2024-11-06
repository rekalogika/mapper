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

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\ObjectCache\Sentinel\CachedTargetObjectNotFoundSentinel;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Array\ObjectWithArray;
use Rekalogika\Mapper\TypeResolver\Implementation\TypeResolver;
use Rekalogika\Mapper\Util\TypeFactory;

class ArrayToArrayTest extends FrameworkTestCase
{
    public function testItMapsArrayToArray(): void
    {
        $foo = new ObjectWithArray();
        $foo->array = ['foo', 'bar'];

        $bar = new ObjectWithArray();
        $bar->array = ['baz'];

        $this->mapper->map($bar, $foo);

        $this->assertEquals($bar->array, $foo->array);
    }
}
