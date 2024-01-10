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

namespace Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto;

use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesDto;

class ObjectWithArrayAccessPropertyDto
{
    /**
     * @var ?\ArrayAccess<int,ObjectWithScalarPropertiesDto>
     */
    public ?\ArrayAccess $property = null;

    public static function initialized(): self
    {
        $instance = new self();

        $instance->property = new \ArrayObject([
            1 => new ObjectWithScalarPropertiesDto(),
            2 => new ObjectWithScalarPropertiesDto(),
            3 => new ObjectWithScalarPropertiesDto(),
        ]);

        return $instance;
    }
}
