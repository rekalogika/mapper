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

namespace Rekalogika\Mapper\Tests\Fixtures\ConstructorAndProperty;

class ObjectWithConstructorArgumentsAndPublicProperties
{
    // @todo test without initialization, add exception to constructor to make
    //       sure that the constructor is not called
    // @phpstan-ignore property.onlyWritten
    private string $foo = 'bar';

    public function __construct(
        public string $id,
        public string $name,
        public string $description,
    ) {}
}
