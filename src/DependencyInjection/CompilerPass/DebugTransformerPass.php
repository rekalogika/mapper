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

namespace Rekalogika\Mapper\DependencyInjection\CompilerPass;

use Rekalogika\Mapper\Debug\TraceableTransformer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DebugTransformerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('rekalogika.mapper.transformer');
        $dataCollector = new Reference('rekalogika.mapper.data_collector');

        foreach ($taggedServices as $serviceId => $tags) {
            $decoratedServiceId = 'debug.' . $serviceId;

            $service = $container->getDefinition($serviceId);
            /** @var array<string,mixed> */
            $tagAttributes = $service->getTag('rekalogika.mapper.transformer')[0] ?? [];
            $service->clearTag('rekalogika.mapper.transformer');

            $container->register($decoratedServiceId, TraceableTransformer::class)
                ->setDecoratedService($serviceId)
                ->addTag('rekalogika.mapper.transformer', $tagAttributes)
                ->setArguments([
                    $service,
                    $dataCollector,
                ]);
        }
    }
}
