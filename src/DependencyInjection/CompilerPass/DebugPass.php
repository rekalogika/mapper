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

use Rekalogika\Mapper\Debug\TraceableMappingFactory;
use Rekalogika\Mapper\Debug\TraceableObjectToObjectMetadataFactory;
use Rekalogika\Mapper\Debug\TraceableTransformer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DebugPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // remove property info cache

        $container->removeDefinition('rekalogika.mapper.cache.property_info');
        $container->removeDefinition('rekalogika.mapper.property_info.cache');

        // decorates all transformers using TraceableTransformer

        $dataCollector = new Reference('rekalogika.mapper.data_collector');
        $taggedServices = $container->findTaggedServiceIds('rekalogika.mapper.transformer');

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

        // decorates ObjectToObjectMetadataFactory

        $serviceId = 'rekalogika.mapper.object_to_object_metadata_factory.cache';
        $decoratedService = $container->getDefinition($serviceId);
        $container->register('debug.' . $serviceId, TraceableObjectToObjectMetadataFactory::class)
            ->setDecoratedService($serviceId)
            ->setArguments([
                $decoratedService,
                $dataCollector,
            ]);

        // decorates mapping factory

        $serviceId = 'rekalogika.mapper.mapping_factory';
        $decoratedService = $container->getDefinition($serviceId);
        $container->register('debug.' . $serviceId, TraceableMappingFactory::class)
            ->setDecoratedService($serviceId, null, 50)
            ->setArguments([
                $decoratedService,
                $dataCollector,
            ]);
    }
}
