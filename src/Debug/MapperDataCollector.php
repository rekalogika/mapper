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

use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\PropertyInfo\Type;

final class MapperDataCollector extends DataCollector
{
    public function getName()
    {
        return 'rekalogika_mapper';
    }

    public function collect(
        Request $request,
        Response $response,
        ?\Throwable $exception = null
    ) {
    }

    /**
     * @param class-string<TransformerInterface> $transformerClass
     */
    public function createTraceData(
        ?string $path,
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        string $transformerClass
    ): TraceData {
        $traceData = new TraceData(
            $path,
            $this->cloneVar($source),
            $this->cloneVar($target),
            $sourceType,
            $targetType,
            $transformerClass
        );

        return $traceData;
    }

    public function collectTraceData(TraceData $traceData): void
    {
        /** @psalm-suppress MixedArrayAssignment */
        $this->data['mappings'][] = $traceData;
    }

    /**
     * @return array<int,TraceData>
     */
    public function getMappings(): array
    {
        /** @var array<int,TraceData> */
        return $this->data['mappings'];
    }
}
