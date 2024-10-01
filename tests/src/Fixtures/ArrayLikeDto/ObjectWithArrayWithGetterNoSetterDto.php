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

class ObjectWithArrayWithGetterNoSetterDto
{
    /**
     * @var array<int,ObjectWithScalarPropertiesDto>
     */
    private array $property = [];

    public static function initialized(): self
    {
        $self = new self();

        $self->property[] = new ObjectWithScalarPropertiesDto();
        $self->property[] = new ObjectWithScalarPropertiesDto();
        $self->property[] = new ObjectWithScalarPropertiesDto();

        return $self;
    }

    /**
     * @return array<int,ObjectWithScalarPropertiesDto>
     */
    public function getProperty(): array
    {
        return $this->property;
    }
}
