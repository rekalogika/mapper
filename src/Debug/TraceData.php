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
    private string $sourceType;
    private string $existingTargetType;
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

    /**
     * @param null|array<int,Type|MixedType> $possibleTargetTypes
     * @param class-string<TransformerInterface> $transformerClass
     */
    public function __construct(
        private ?string $path,
        mixed $source,
        mixed $existingTargetValue,
        private ?array $possibleTargetTypes,
        private ?Type $selectedTargetType,
        private string $transformerClass,
        private bool $sourceTypeGuessed,
    ) {
        $this->sourceType = \get_debug_type($source);
        $this->existingTargetType = \get_debug_type($existingTargetValue);
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

    /**
     * @return null|array<int,Type|MixedType>
     */
    public function getPossibleTargetTypes(): ?array
    {
        return $this->possibleTargetTypes;
    }

    public function getPossibleTargetTypesHtml(): string
    {
        if ($this->possibleTargetTypes === null) {
            return "__unknown__";
        }
        return TypeUtil::getTypeStringHtml($this->possibleTargetTypes);
        ;
    }

    public function getSelectedTargetType(): ?Type
    {
        return $this->selectedTargetType;
    }

    public function getSelectedTargetTypeHtml(): string
    {
        if ($this->selectedTargetType !== null) {
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
     * @param class-string|null $class
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

        if ($file !== null) {
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
     * @return null|array{file:string|null,line:int|null,function:string|null,class:class-string|null,type:string|null,name:string|null}
     */
    public function getCaller(): ?array
    {
        if ($this->callerFunction === null) {
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
}
