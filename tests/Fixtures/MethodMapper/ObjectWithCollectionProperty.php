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

namespace Rekalogika\Mapper\Tests\Fixtures\MethodMapper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;

class ObjectWithCollectionProperty
{
    /**
     * @var Collection<int,ObjectWithScalarProperties>
     */
    public Collection $property;

    public function __construct()
    {
        $this->property = new ArrayCollection();
        $this->property->add(new ObjectWithScalarProperties());
        $this->property->add(new ObjectWithScalarProperties());
        $this->property->add(new ObjectWithScalarProperties());
    }
}
