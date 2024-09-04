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
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
final class TraceableMappingFactory implements MappingFactoryInterface, ResetInterface
{
    private bool $mappingCollected = false;

    public function __construct(
        private readonly MappingFactoryInterface $decorated,
        private readonly MapperDataCollector $dataCollector,
    ) {}

    #[\Override]
    public function reset(): void
    {
        $this->mappingCollected = false;
    }

    #[\Override]
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
