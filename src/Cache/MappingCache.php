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

use Symfony\Component\PropertyInfo\Type;

class MappingCache
{
    /**
     * @var array<string,array<string,true>>
     */
    private array $mapping = [];

    public function saveMapping(Type $source, Type $target): void
    {
        $sourceHash = hash('xxh128', serialize($source));
        $targetHash = hash('xxh128', serialize($target));

        $this->mapping[$sourceHash][$targetHash] = true;
    }

    public function containsMapping(Type $source, Type $target): bool
    {
        $sourceHash = hash('xxh128', serialize($source));
        $targetHash = hash('xxh128', serialize($target));

        return isset($this->mapping[$sourceHash][$targetHash]);
    }
}
