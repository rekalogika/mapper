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

namespace Rekalogika\Mapper\Transformer\ArrayLikeMetadata;

use Psr\Cache\CacheItemPoolInterface;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadataFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

final class CachingArrayLikeMetadataFactory implements ArrayLikeMetadataFactoryInterface
{
    /**
     * @var array<string,ArrayLikeMetadata>
     */
    private array $cache = [];

    public function __construct(
        private ArrayLikeMetadataFactoryInterface $decorated,
        private CacheItemPoolInterface $cacheItemPool,
    ) {
    }

    public function createArrayLikeMetadata(Type $targetType): ArrayLikeMetadata
    {
        $cacheKey = \rawurlencode(\serialize($targetType));

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $cacheItem = $this->cacheItemPool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            try {
                /** @var mixed */
                $cached = $cacheItem->get();

                if ($cached instanceof ArrayLikeMetadata) {
                    return $this->cache[$cacheKey] = $cached;
                }
            } catch (\Throwable) {
            }

            unset($this->cache[$cacheKey]);
            $this->cacheItemPool->deleteItem($cacheKey);
        }

        $arrayLikeMetadata = $this->decorated
            ->createArrayLikeMetadata($targetType);

        $cacheItem->set($arrayLikeMetadata);
        $this->cacheItemPool->save($cacheItem);

        return $this->cache[$cacheKey] = $arrayLikeMetadata;
    }
}
