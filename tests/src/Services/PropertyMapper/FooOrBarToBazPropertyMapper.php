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

namespace Rekalogika\Mapper\Tests\Services\PropertyMapper;

use Rekalogika\Mapper\Attribute\AsPropertyMapper;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\Bar;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\Baz;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\Foo;

class FooOrBarToBazPropertyMapper
{
    #[AsPropertyMapper(targetClass: Baz::class, property: 'bazName')]
    public function mapName(
        Foo|Bar $fooOrBar,
    ): string {
        if ($fooOrBar instanceof Foo) {
            return $fooOrBar->fooName;
        } else {
            return $fooOrBar->barName;
        }
    }
}
