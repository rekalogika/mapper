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

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MapperDataCollector extends AbstractDataCollector
{
    public function getName(): string
    {
        return 'rekalogika_mapper';
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

    /**
     * @return array<int,TraceData>
     */
    public function getMappings(): array
    {
        /** @var array<int,TraceData> */
        return $this->data['mappings'];
    }

    public static function getTemplate(): string
    {
        return "@RekalogikaMapper/data_collector.html.twig";
    }
}
