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

use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarExporter\LazyObjectInterface;

final class TraceData
{
    private string $sourceType;
    private string $existingTargetType;
    private string $possibleTargetTypes;
    private string $selectedTargetType;
    private ?string $resultType = null;
    private ?float $time = null;

    /** @var array<int,self> */
    private array $nestedTraceData = [];

    /**
     * @param array<int,Type|MixedType> $possibleTargetTypes
     * @param class-string<TransformerInterface> $transformerClass
     */
    public function __construct(
        private ?string $path,
        mixed $source,
        mixed $existingTargetValue,
        ?array $possibleTargetTypes,
        ?Type $selectedTargetType,
        private string $transformerClass,
        private bool $sourceTypeGuessed,
    ) {
        $this->sourceType = \get_debug_type($source);
        $this->existingTargetType = \get_debug_type($existingTargetValue);

        if ($selectedTargetType !== null) {
            $this->selectedTargetType = TypeUtil::getTypeStringHtml($selectedTargetType);
        } else {
            $this->selectedTargetType = 'mixed';
        }

        if ($possibleTargetTypes === null) {
            $this->possibleTargetTypes = "__unknown__";
        } else {
            $this->possibleTargetTypes = TypeUtil::getTypeStringHtml($possibleTargetTypes);
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

    public function getPossibleTargetTypes(): string
    {
        return $this->possibleTargetTypes;
    }

    public function getSelectedTargetType(): string
    {
        return $this->selectedTargetType;
    }

    public function getResultType(): string
    {
        return $this->resultType ?? '__unknown__';
    }

    public function isLazyLoadingResult(): bool
    {
        $class = $this->getResultType();
        if (!class_exists($class)) {
            return false;
        }

        return is_a($class, LazyObjectInterface::class, true);
    }

    public function getTotalMappingsIncludingSubMappings(): int
    {
        $total = 1;

        foreach ($this->nestedTraceData as $traceData) {
            $total += $traceData->getTotalMappingsIncludingSubMappings();
        }

        return $total;
    }

    public function isSourceTypeGuessed(): bool
    {
        return $this->sourceTypeGuessed;
    }
}
