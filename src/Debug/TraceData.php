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

use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\VarDumper\Cloner\Data;

final class TraceData
{
    private string $sourceType;
    private string $existingTargetType;
    private string $targetType;
    private ?string $resultType = null;
    private ?float $time = null;

    /** @var array<int,self> */
    private array $nestedTraceData = [];

    /**
     * @param class-string<TransformerInterface> $transformerClass
     */
    public function __construct(
        private ?string $path,
        mixed $source,
        mixed $existingTargetValue,
        ?Type $targetType,
        private string $transformerClass,
    ) {
        $this->sourceType = \get_debug_type($source);
        $this->existingTargetType = \get_debug_type($existingTargetValue);

        if ($targetType !== null) {
            $this->targetType = TypeUtil::getTypeStringHtml($targetType);
        } else {
            $this->targetType = 'mixed';
        }
    }

    public function finalizeTime(float $time): void
    {
        if (count($this->nestedTraceData) === 0) {
            // If this is the last trace data (no nested trace data)
            $this->time = $time;
        } else {
            // If this is not the last trace data (has nested trace data), we
            // don't use the given time, but we calculate the time from the
            // nested trace data
            $this->time = array_sum(array_map(fn (self $traceData) => $traceData->getTime(), $this->nestedTraceData));
        }
    }

    public function finalizeResult(mixed $result): void
    {
        $this->resultType = \get_debug_type($result);
    }

    public function getTime(): float
    {
        return $this->time ?? 0;
    }

    /**
     * @return class-string<TransformerInterface>
     */
    public function getTransformerClass(): string
    {
        return $this->transformerClass;
    }

    /**
     * @return array<int,self>
     */
    public function getNestedTraceData(): array
    {
        return $this->nestedTraceData;
    }

    public function addNestedTraceData(self $traceData): void
    {
        $this->nestedTraceData[] = $traceData;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getExistingTargetType(): string
    {
        return $this->existingTargetType;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function getResultType(): string
    {
        return $this->resultType ?? '__unknown__';
    }

    public function getTotalMappingsIncludingSubMappings(): int
    {
        $total = 1;

        foreach ($this->nestedTraceData as $traceData) {
            $total += $traceData->getTotalMappingsIncludingSubMappings();
        }

        return $total;
    }
}
