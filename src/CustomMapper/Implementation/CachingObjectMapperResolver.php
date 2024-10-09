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

namespace Rekalogika\Mapper\CustomMapper\Implementation;

use Psr\Cache\CacheItemPoolInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableCacheInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableObjectMapperResolverInterface;
use Rekalogika\Mapper\CustomMapper\ObjectMapperResolverInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;

/**
 * @internal
 */
final class CachingObjectMapperResolver implements
    ObjectMapperResolverInterface,
    WarmableObjectMapperResolverInterface
{
    /**
     * @var array<class-string,array<class-string,ServiceMethodSpecification>>
     */
    private array $objectMapperCache = [];

    public function __construct(
        private readonly ObjectMapperResolverInterface $objectMapperResolver,
        private readonly CacheItemPoolInterface $cacheItemPool,
    ) {}

    #[\Override]
    public function getObjectMapper(
        string $sourceClass,
        string $targetClass,
    ): ServiceMethodSpecification {
        $cacheKey = hash('xxh128', $sourceClass . $targetClass);

        if (isset($this->objectMapperCache[$sourceClass][$targetClass])) {
            return $this->objectMapperCache[$sourceClass][$targetClass];
        }

        $cacheItem = $this->cacheItemPool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            try {
                /** @var mixed */
                $result = $cacheItem->get();
            } catch (\Throwable) {
                $result = null;
                $this->cacheItemPool->deleteItem($cacheKey);
            }

            if ($result instanceof ServiceMethodSpecification) {
                return $this->objectMapperCache[$sourceClass][$targetClass] = $result;
            }

            $this->cacheItemPool->deleteItem($cacheKey);
        }

        $objectMapper = $this->objectMapperResolver->getObjectMapper($sourceClass, $targetClass);

        $cacheItem->set($objectMapper);
        $this->cacheItemPool->save($cacheItem);

        return $this->objectMapperCache[$sourceClass][$targetClass] = $objectMapper;
    }

    public function warmingGetObjectMapper(
        string $sourceClass,
        string $targetClass,
    ): ServiceMethodSpecification {
        $result = $this->objectMapperResolver
            ->getObjectMapper($sourceClass, $targetClass);

        if (!$this->cacheItemPool instanceof WarmableCacheInterface) {
            return $result;
        }

        $cacheKey = hash('xxh128', $sourceClass . $targetClass);
        $cacheItem = $this->cacheItemPool->getWarmedUpItem($cacheKey);
        $cacheItem->set($result);

        $this->cacheItemPool->saveWarmedUp($cacheItem);

        return $result;
    }
}
