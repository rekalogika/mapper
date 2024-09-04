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

use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * @internal
 */
final class TraceData
{
    private readonly string $sourceType;

    private readonly string $existingTargetType;

    private ?string $resultType = null;

    private ?float $time = null;

    /** @var array<int,self> */
    private array $nestedTraceData = [];

    private ?string $callerFile = null;

    private ?int $callerLine = null;

    private ?string $callerFunction = null;

    /** @var class-string */
    private ?string $callerClass = null;

    private ?string $callerType = null;

    private ?string $callerName = null;

    private bool $refused = false;

    /**
     * @param null|array<int,MixedType|Type>     $possibleTargetTypes
     * @param class-string<TransformerInterface> $transformerClass
     */
    public function __construct(
        private readonly ?string $path,
        mixed $source,
        mixed $existingTargetValue,
        private readonly ?array $possibleTargetTypes,
        private readonly ?Type $selectedTargetType,
        private readonly string $transformerClass,
        private readonly bool $sourceTypeGuessed,
    ) {
        $this->sourceType = \get_debug_type($source);
        $this->existingTargetType = \get_debug_type($existingTargetValue);
    }

    public function refusedToTransform(): void
    {
        $this->refused = true;
    }

    public function finalize(float $time, mixed $result): void
    {
        $this->finalizeTime($time);
        $this->finalizeResult($result);
    }

    private function finalizeTime(float $time): void
    {
        if ([] === $this->nestedTraceData) {
            // If this is the last trace data (no nested trace data)
            $this->time = $time;
        } else {
            // If this is not the last trace data (has nested trace data), we
            // don't use the given time, but we calculate the time from the
            // nested trace data
            $this->time = array_sum(array_map(fn (self $traceData): float => $traceData->getTime(), $this->nestedTraceData));
        }
    }

    private function finalizeResult(mixed $result): void
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

    /**
     * @return array<int,self>
     */
    public function getAcceptedNestedTraceData(): array
    {
        return array_filter($this->nestedTraceData, fn (self $traceData): bool => !$traceData->isRefused());
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

    /**
     * @return null|array<int,MixedType|Type>
     */
    public function getPossibleTargetTypes(): ?array
    {
        return $this->possibleTargetTypes;
    }

    public function getPossibleTargetTypesHtml(): string
    {
        if (null === $this->possibleTargetTypes) {
            return '__unknown__';
        }

        return TypeUtil::getTypeStringHtml($this->possibleTargetTypes);
    }

    public function getSelectedTargetType(): ?Type
    {
        return $this->selectedTargetType;
    }

    public function getSelectedTargetTypeHtml(): string
    {
        if (null !== $this->selectedTargetType) {
            return TypeUtil::getTypeStringHtml($this->selectedTargetType);
        }

        return 'mixed';
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

    /**
     * @param null|class-string $class
     */
    public function setCaller(
        ?string $file,
        ?int $line,
        string $function,
        ?string $class,
        ?string $type
    ): self {
        $this->callerFile = $file ?? 'unknown';
        $this->callerLine = $line;
        $this->callerFunction = $function;
        $this->callerClass = $class;
        $this->callerType = $type;

        if (null !== $file) {
            $name = str_replace('\\', '/', $file);
            $pos = strrpos($name, '/');
            if (is_int($pos)) {
                $name = substr($name, $pos + 1);
            }
        } else {
            $name = 'unknown';
        }

        $this->callerName = $name;

        return $this;
    }

    /**
     * @return null|array{file:null|string,line:null|int,function:null|string,class:null|class-string,type:null|string,name:null|string}
     */
    public function getCaller(): ?array
    {
        if (null === $this->callerFunction) {
            return null;
        }

        return [
            'file' => $this->callerFile,
            'line' => $this->callerLine,
            'function' => $this->callerFunction,
            'class' => $this->callerClass,
            'type' => $this->callerType,
            'name' => $this->callerName,
        ];
    }

    /**
     * Get the value of refused.
     */
    public function isRefused(): bool
    {
        return $this->refused;
    }
}
