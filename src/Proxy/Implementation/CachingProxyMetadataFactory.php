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
use Rekalogika\Mapper\Proxy\Metadata\PropertyMetadata;
use Rekalogika\Mapper\Proxy\ProxyMetadataFactoryInterface;

/**
 * @internal
 */
final class CachingProxyMetadataFactory implements ProxyMetadataFactoryInterface
{
    public function __construct(
        private readonly ProxyMetadataFactoryInterface $decorated,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    #[\Override]
    public function getMetadata(string $class): ClassMetadata
    {
        $cacheItem = $this->cache->getItem($class);

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
