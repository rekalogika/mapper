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

namespace Rekalogika\Mapper\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

interface WarmableCacheInterface extends CacheItemPoolInterface
{
    public function getWarmedUpItem(string $key): CacheItemInterface;

    public function saveWarmedUp(CacheItemInterface $item): bool;
}
