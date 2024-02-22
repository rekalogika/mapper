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

namespace Rekalogika\Mapper\ObjectCache\Implementation;

use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\ObjectCache\ObjectCacheFactoryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;

final readonly class ObjectCacheFactory implements ObjectCacheFactoryInterface
{
    public function __construct(
        private TypeResolverInterface $typeResolver
    ) {
    }

    public function createObjectCache(): ObjectCache
    {
        return new ObjectCache($this->typeResolver);
    }
}
