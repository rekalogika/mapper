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

namespace Rekalogika\Mapper\Tests\Fixtures\ArrayLike;

use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;

class ObjectWithArrayPropertyWithStringKey
{
    /**
     * @var array<string,ObjectWithScalarProperties>
     */
    public array $property;

    public function __construct()
    {
        $this->property = [
            'a' => new ObjectWithScalarProperties(),
            'b' => new ObjectWithScalarProperties(),
            'c' => new ObjectWithScalarProperties(),
        ];
    }
}
