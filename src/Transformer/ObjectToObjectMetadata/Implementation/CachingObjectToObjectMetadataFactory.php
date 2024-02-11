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
use Rekalogika\Mapper\Exception\RuntimeException;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Util\ClassUtil;

final class CachingObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    /**
     * @var array<string,ObjectToObjectMetadata>
     */
    private array $cache = [];

    public function __construct(
        private ObjectToObjectMetadataFactoryInterface $decorated,
        private CacheItemPoolInterface $cacheItemPool,
        private bool $debug,
    ) {
    }

    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        return $this->getFromMemoryCache($sourceClass, $targetClass);
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    private function getFromMemoryCache(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        $cacheKey = $sourceClass . ':' . $targetClass;

        if (isset($this->cache[$cacheKey])) {
            $result = $this->cache[$cacheKey];

            if (!$this->isObjectToObjectMetadataStale($result)) {
                return $result;
            }
        }

        return $this->cache[$cacheKey] =
            $this->getFromCache($sourceClass, $targetClass);
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    private function getFromCache(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        $cacheKey = hash('xxh128', $sourceClass . $targetClass);

        $cacheItem = $this->cacheItemPool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            try {
                /** @var mixed */
                $cached = $cacheItem->get();

                if (!$cached instanceof ObjectToObjectMetadata) {
                    throw new RuntimeException();
                }

                if ($this->isObjectToObjectMetadataStale($cached)) {
                    throw new RuntimeException();
                }

                return $cached;
            } catch (\Throwable) {
            }

            $this->cacheItemPool->deleteItem($cacheKey);
        }

        $objectToObjectMetadata = $this->decorated
            ->createObjectToObjectMetadata($sourceClass, $targetClass);

        $cacheItem->set($objectToObjectMetadata);
        $this->cacheItemPool->save($cacheItem);

        return $objectToObjectMetadata;
    }

    private function isObjectToObjectMetadataStale(
        ObjectToObjectMetadata $objectToObjectMetadata
    ): bool {
        if (!$this->debug) {
            return false;
        }

        $sourceModifiedTime = $objectToObjectMetadata->getSourceModifiedTime();
        $targetModifiedTime = $objectToObjectMetadata->getTargetModifiedTime();

        $sourceFileModifiedTime = ClassUtil::getLastModifiedTime(
            $objectToObjectMetadata->getSourceClass()
        );

        $targetFileModifiedTime = ClassUtil::getLastModifiedTime(
            $objectToObjectMetadata->getTargetClass()
        );

        return $sourceFileModifiedTime > $sourceModifiedTime
            || $targetFileModifiedTime > $targetModifiedTime;
    }
}
