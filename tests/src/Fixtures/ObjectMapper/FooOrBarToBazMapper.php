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

namespace Rekalogika\Mapper\Tests\Fixtures\ObjectMapper;

use Rekalogika\Mapper\Attribute\AsObjectMapper;

class FooOrBarToBazMapper
{
    #[AsObjectMapper]
    public function mapPersonToPersonDto(
        Foo|Bar $fooOrBar,
    ): Baz {
        return new Baz();
    }
}
