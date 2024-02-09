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

namespace Rekalogika\Mapper\Debug;

use Rekalogika\Mapper\Mapping\Mapping;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class TraceableMappingFactory implements
    MappingFactoryInterface,
    CacheWarmerInterface
{
    private bool $mappingCollected = false;

    public function __construct(
        private MappingFactoryInterface $decorated,
        private MapperDataCollector $dataCollector
    ) {
    }

    public function isOptional(): bool
    {
        return $this->decorated instanceof CacheWarmerInterface && $this->decorated->isOptional();
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        if ($this->decorated instanceof CacheWarmerInterface) {
            return $this->decorated->warmUp($cacheDir, $buildDir);
        }

        return [];
    }

    public function getMapping(): Mapping
    {
        if ($this->mappingCollected) {
            return $this->decorated->getMapping();
        }

        $mapping = $this->decorated->getMapping();

        $this->dataCollector->collectMappingTable($mapping);
        $this->mappingCollected = true;

        return $mapping;
    }
}
