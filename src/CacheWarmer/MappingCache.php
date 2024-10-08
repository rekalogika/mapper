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

namespace Rekalogika\Mapper\CacheWarmer;

use Symfony\Component\PropertyInfo\Type;

class MappingCache
{
    /**
     * @var array<string,true>
     */
    private array $mapping = [];

    public function saveMapping(
        Type $source,
        Type $target,
        string $transformerServiceId,
    ): void {
        $hash = hash('xxh128', serialize([$source, $target, $transformerServiceId]));

        $this->mapping[$hash] = true;
    }

    public function containsMapping(
        Type $source,
        Type $target,
        string $transformerServiceId,
    ): bool {
        $hash = hash('xxh128', serialize([$source, $target, $transformerServiceId]));

        return isset($this->mapping[$hash]);
    }
}
