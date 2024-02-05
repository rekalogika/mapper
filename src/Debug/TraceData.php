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
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\VarDumper\Cloner\Data;

final class TraceData
{
    private ?float $time = null;

    /** @var array<int,self> */
    private array $nestedTraceData = [];

    /**
     * @param class-string<TransformerInterface> $transformerClass
     */
    public function __construct(
        private ?string $path,
        private Data $source,
        private Data $target,
        private ?Type $sourceType,
        private ?Type $targetType,
        private string $transformerClass,
    ) {
    }

    public function getSourceType(): ?Type
    {
        return $this->sourceType;
    }

    public function getTargetType(): ?Type
    {
        return $this->targetType;
    }

    public function finalizeTime(float $time): self
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

        return $this;
    }

    public function getTime(): float
    {
        if ($this->time === null) {
            throw new LogicException('Time is not set');
        }

        return $this->time;
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

    public function getSource(): Data
    {
        return $this->source;
    }

    public function getTarget(): Data
    {
        return $this->target;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}
