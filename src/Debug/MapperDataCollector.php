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

use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MapperDataCollector extends AbstractDataCollector
{
    public function getName(): string
    {
        return 'rekalogika_mapper';
    }

    public static function getTemplate(): string
    {
        return "@RekalogikaMapper/data_collector.html.twig";
    }

    public function collect(
        Request $request,
        Response $response,
        ?\Throwable $exception = null
    ) {
    }

    public function collectTraceData(TraceData $traceData): void
    {
        /** @psalm-suppress MixedArrayAssignment */
        $this->data['mappings'][] = $traceData;
    }

    public function collectObjectToObjectMetadata(
        ObjectToObjectMetadata $objectToObjectMetadata
    ): void {
        $key = hash('xxh128', serialize($objectToObjectMetadata));
        /** @psalm-suppress MixedArrayAssignment */
        $this->data['object_to_object_metadata'][$key] = $objectToObjectMetadata;
    }

    /**
     * @return array<int,TraceData>
     */
    public function getMappings(): array
    {
        /** @var array<int,TraceData> */
        return $this->data['mappings'] ?? [];
    }

    /**
     * @return array<int,ObjectToObjectMetadata>
     */
    public function getObjectToObjectMetadatas(): array
    {
        /** @psalm-suppress MixedArgument */
        return array_values($this->data['object_to_object_metadata'] ?? []);
    }

    private ?int $totalMappings = null;

    public function getTotalMappings(): int
    {
        if ($this->totalMappings !== null) {
            return $this->totalMappings;
        }

        return $this->totalMappings = count($this->getMappings());
    }

    private ?int $totalMappingsIncludingSubMappings = null;

    public function getTotalMappingsIncludingSubMappings(): int
    {
        if ($this->totalMappingsIncludingSubMappings !== null) {
            return $this->totalMappingsIncludingSubMappings;
        }

        $total = 0;

        foreach ($this->getMappings() as $traceData) {
            $total += $traceData->getTotalMappingsIncludingSubMappings();
        }

        return $this->totalMappingsIncludingSubMappings = $total;
    }

    private ?float $totalTime = null;

    public function getTotalTime(): float
    {
        if ($this->totalTime !== null) {
            return $this->totalTime;
        }

        return $this->totalTime = array_sum(array_map(
            fn (TraceData $traceData) => $traceData->getTime(),
            $this->getMappings()
        ));
    }
}
