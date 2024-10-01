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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;

class ObjectWithCollectionWithGetterNoSetterDto
{
    /**
     * @var Collection<int,ObjectWithScalarPropertiesDto>
     */
    private Collection $property;

    public function __construct()
    {
        $this->property = new ArrayCollection();
    }

    public static function initialized(): self
    {
        $self = new self();

        $self->property->add(new ObjectWithScalarPropertiesDto());
        $self->property->add(new ObjectWithScalarPropertiesDto());
        $self->property->add(new ObjectWithScalarPropertiesDto());

        return $self;
    }

    /**
     * @return Collection<int,ObjectWithScalarPropertiesDto>
     */
    public function getProperty(): Collection
    {
        return $this->property;
    }
}
