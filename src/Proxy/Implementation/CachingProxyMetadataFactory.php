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

namespace Rekalogika\Mapper\Proxy\Implementation;

use Psr\Cache\CacheItemPoolInterface;
use Rekalogika\Mapper\Proxy\Metadata\ClassMetadata;
use Rekalogika\Mapper\Proxy\ProxyMetadataFactoryInterface;

/**
 * @internal
 */
final readonly class CachingProxyMetadataFactory implements ProxyMetadataFactoryInterface
{
    public function __construct(
        private ProxyMetadataFactoryInterface $decorated,
        private CacheItemPoolInterface $cache,
    ) {}

    #[\Override]
    public function getMetadata(string $class): ClassMetadata
    {
        $key = hash('xxh128', $class);
        $cacheItem = $this->cache->getItem($key);

        if ($cacheItem->isHit()) {
            /** @psalm-suppress MixedAssignment */
            $result = $cacheItem->get();

            if ($result instanceof ClassMetadata) {
                return $result;
            }

            $this->cache->deleteItem($class);
        }

        $result = $this->decorated->getMetadata($class);

        $cacheItem->set($result);
        $this->cache->save($cacheItem);

        return $result;
    }
}
