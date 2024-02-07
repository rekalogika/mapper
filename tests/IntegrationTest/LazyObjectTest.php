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
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithId;
use Rekalogika\Mapper\Tests\Fixtures\LazyObject\ObjectWithIdDto;

class LazyObjectTest extends AbstractFrameworkTest
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
        $this->expectExceptionMessage('This method should not be called');
        $source = new ObjectWithId();
        $target = $this->mapper->map($source, ObjectWithIdDto::class);
        $foo = $target->name;
    }
}
