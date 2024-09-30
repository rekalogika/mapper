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

namespace Rekalogika\Mapper\Tests\Services\ObjectMapper;

use Rekalogika\Mapper\Attribute\AsObjectMapper;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\Bar;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\Baz;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\Foo;

class FooOrBarToBazMapper
{
    #[AsObjectMapper]
    public function mapPersonToPersonDto(
        Foo|Bar $fooOrBar,
    ): Baz
        return new Baz();
    }

    #[AsObjectMapper]
    public function mapBazToFoo(
        Baz $baz,
        Foo $foo,
    ): Foo {
        return new Foo();
    }
}
