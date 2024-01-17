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

namespace Rekalogika\Mapper\Transformer\ObjectMappingResolver;

use Psr\Cache\CacheItemPoolInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMapping;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMappingResolverInterface;

final class CachingObjectMappingResolver implements ObjectMappingResolverInterface
{
    /**
     * @var array<string,ObjectMapping>
     */
    private array $cache = [];

    public function __construct(
        private ObjectMappingResolverInterface $decorated,
        private CacheItemPoolInterface $cacheItemPool,
    ) {
    }

    public function resolveObjectMapping(
        string $sourceClass,
        string $targetClass,
        Context $context
    ): ObjectMapping {
        $cacheKey = $sourceClass . ':' . $targetClass;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $cacheItem = $this->cacheItemPool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            try {
                /** @var mixed */
                $cached = $cacheItem->get();

                if ($cached instanceof ObjectMapping) {
                    return $this->cache[$cacheKey] = $cached;
                }
            } catch (\Throwable) {
            }

            unset($this->cache[$cacheKey]);
            $this->cacheItemPool->deleteItem($cacheKey);
        }

        $objectMapping = $this->decorated
            ->resolveObjectMapping($sourceClass, $targetClass, $context);

        $cacheItem->set($objectMapping);
        $this->cacheItemPool->save($cacheItem);

        return $this->cache[$cacheKey] = $objectMapping;
    }
}
