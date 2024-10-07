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

namespace Rekalogika\Mapper\TransformerRegistry\Implementation;

use Psr\Cache\CacheItemPoolInterface;
use Rekalogika\Mapper\Cache\WarmableCacheInterface;
use Rekalogika\Mapper\Cache\WarmableTransformerRegistryInterface;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\TransformerRegistry\SearchResult;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class CachingTransformerRegistry implements
    TransformerRegistryInterface,
    WarmableTransformerRegistryInterface
{
    /**
     * @var array<string,SearchResult>
     */
    private array $findBySourceAndTargetTypesCache = [];

    public function __construct(
        private readonly TransformerRegistryInterface $decorated,
        private readonly CacheItemPoolInterface $cacheItemPool,
    ) {}

    /**
     * @var array<string,TransformerInterface>
     */
    private array $transformers = [];

    #[\Override]
    public function get(string $id): TransformerInterface
    {
        return $this->transformers[$id] ?? ($this->transformers[$id] = $this->decorated->get($id));
    }

    /**
     * @param array<array-key,Type|MixedType> $sourceTypes
     * @param array<array-key,Type|MixedType> $targetTypes
     */
    private function getCacheKey(array $sourceTypes, array $targetTypes): string
    {
        return hash('xxh128', serialize([$sourceTypes, $targetTypes]));
    }

    #[\Override]
    public function findBySourceAndTargetTypes(
        array $sourceTypes,
        array $targetTypes,
    ): SearchResult {
        $cacheKey = $this->getCacheKey($sourceTypes, $targetTypes);

        if (isset($this->findBySourceAndTargetTypesCache[$cacheKey])) {
            return $this->findBySourceAndTargetTypesCache[$cacheKey];
        }

        $cacheItem = $this->cacheItemPool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            /** @var mixed */
            $result = $cacheItem->get();

            if ($result instanceof SearchResult) {
                return $this->findBySourceAndTargetTypesCache[$cacheKey] = $result;
            }

            $this->cacheItemPool->deleteItem($cacheKey);
        }

        $result = $this->decorated->findBySourceAndTargetTypes($sourceTypes, $targetTypes);

        $cacheItem->set($result);
        $this->cacheItemPool->save($cacheItem);

        return $this->findBySourceAndTargetTypesCache[$cacheKey] = $result;
    }

    /**
     * @param array<array-key,Type|MixedType> $sourceTypes
     * @param array<array-key,Type|MixedType> $targetTypes
     */
    public function warmFindBySourceAndTargetTypes(
        array $sourceTypes,
        array $targetTypes,
    ): ?SearchResult {
        $result = $this->decorated
            ->findBySourceAndTargetTypes($sourceTypes, $targetTypes);

        if (!$this->cacheItemPool instanceof WarmableCacheInterface) {
            return $result;
        }

        $cacheKey = $this->getCacheKey($sourceTypes, $targetTypes);
        $cacheItem = $this->cacheItemPool->getWarmedUpItem($cacheKey);
        $cacheItem->set($result);
        $this->cacheItemPool->saveWarmedUp($cacheItem);

        return $result;
    }
}
