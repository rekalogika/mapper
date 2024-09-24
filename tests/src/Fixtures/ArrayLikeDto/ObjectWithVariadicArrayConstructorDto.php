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

use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;

class ObjectWithVariadicArrayConstructorDto
{
    /**
     * @var ?array<array-key,ObjectWithScalarPropertiesDto>
     */
    private ?array $property = null;

    public function __construct(ObjectWithScalarPropertiesDto ...$property)
    {
        $this->property = $property;
    }

    public static function initialized(): self
    {
        $instance = new self();

        $instance->property = [
            1 => new ObjectWithScalarPropertiesDto(),
            2 => new ObjectWithScalarPropertiesDto(),
            3 => new ObjectWithScalarPropertiesDto(),
        ];

        return $instance;
    }

    /**
     * @return ?array<array-key,ObjectWithScalarPropertiesDto>
     */
    public function getProperty(): ?array
    {
        return $this->property;
    }
}
