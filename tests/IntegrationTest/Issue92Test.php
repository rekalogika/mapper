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
use Rekalogika\Mapper\Tests\Fixtures\Issue92\Foo;
use Rekalogika\Mapper\Tests\Fixtures\Issue92\FooDto;

class Issue92Test extends FrameworkTestCase
{
    public function testIssue92(): void
    {
        $dto = new FooDto('bar', 'baz');
        $foo = $this->mapper->map($dto, Foo::class);

        $this->assertSame('bar', $foo->bar);
        $this->assertSame('baz', $foo->baz);
    }
}
