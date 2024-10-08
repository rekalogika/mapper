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

namespace Rekalogika\Mapper\CacheWarmer\Implementation;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableCacheInterface;
use Rekalogika\Mapper\Exception\BadMethodCallException;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

final readonly class WarmableCacheDecorator implements WarmableCacheInterface
{
    private CacheItemPoolInterface $readonlyCache;

    public function __construct(
        private CacheItemPoolInterface $writableCache,
        string $readOnlyCacheDirectory,
        string $namespace = '',
    ) {
        $this->readonlyCache = new PhpFilesAdapter(
            namespace: $namespace,
            directory: $readOnlyCacheDirectory,
            defaultLifetime: 0,
            appendOnly: true,
        );
    }

    public function getItem(string $key): CacheItemInterface
    {
        $item = $this->readonlyCache->getItem($key);

        if ($item->isHit()) {
            return $item;
        }

        return $this->writableCache->getItem($key);
    }

    public function getWarmedUpItem(string $key): CacheItemInterface
    {
        return $this->readonlyCache->getItem($key);
    }

    /**
     * @param array<array-key,string> $keys
     * @return iterable<mixed>
     */
    public function getItems(array $keys = []): iterable
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function hasItem(string $key): bool
    {
        return $this->readonlyCache->hasItem($key)
            || $this->writableCache->hasItem($key);
    }

    public function clear(): bool
    {
        return $this->writableCache->clear();
    }

    public function deleteItem(string $key): bool
    {
        return $this->writableCache->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->writableCache->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->writableCache->save($item);
    }

    public function saveWarmedUp(CacheItemInterface $item): bool
    {
        return $this->readonlyCache->save($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->writableCache->saveDeferred($item);
    }

    public function commit(): bool
    {
        return $this->writableCache->commit();
    }
}
