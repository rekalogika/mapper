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

class ObjectWithSplObjectStorageProperty
{
    /**
     * @var \SplObjectStorage<ObjectWithScalarProperties,ObjectWithScalarProperties>
     */
    public \SplObjectStorage $property;

    public function __construct()
    {
        $this->property = new \SplObjectStorage();
        $this->property[new ObjectWithScalarProperties()] = new ObjectWithScalarProperties();
        $this->property[new ObjectWithScalarProperties()] = new ObjectWithScalarProperties();
        $this->property[new ObjectWithScalarProperties()] = new ObjectWithScalarProperties();
    }

}
