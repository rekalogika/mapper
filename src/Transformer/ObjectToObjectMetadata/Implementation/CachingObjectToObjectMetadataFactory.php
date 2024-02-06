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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation;

use Psr\Cache\CacheItemPoolInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;

final class CachingObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    /**
     * @var array<string,ObjectToObjectMetadata>
     */
    private array $cache = [];

    public function __construct(
        private ObjectToObjectMetadataFactoryInterface $decorated,
        private CacheItemPoolInterface $cacheItemPool,
    ) {
    }

    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
        Context $context
    ): ObjectToObjectMetadata {
        $cacheKey = $sourceClass . ':' . $targetClass;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $cacheItem = $this->cacheItemPool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            try {
                /** @var mixed */
                $cached = $cacheItem->get();

                if ($cached instanceof ObjectToObjectMetadata) {
                    return $this->cache[$cacheKey] = $cached;
                }
            } catch (\Throwable) {
            }

            unset($this->cache[$cacheKey]);
            $this->cacheItemPool->deleteItem($cacheKey);
        }

        $objectToObjectMetadata = $this->decorated
            ->createObjectToObjectMetadata($sourceClass, $targetClass, $context);

        $cacheItem->set($objectToObjectMetadata);
        $this->cacheItemPool->save($cacheItem);

        return $this->cache[$cacheKey] = $objectToObjectMetadata;
    }
}
