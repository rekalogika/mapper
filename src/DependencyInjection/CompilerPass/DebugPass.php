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

/**
 * @internal
 */
final readonly class DebugPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        // decorates all transformers using TraceableTransformer

        $dataCollector = new Reference('rekalogika.mapper.data_collector');
        $taggedServices = $container->findTaggedServiceIds('rekalogika.mapper.transformer');

        foreach (array_keys($taggedServices) as $serviceId) {
            $decoratedServiceId = 'debug.'.$serviceId;

            $container->register($decoratedServiceId, TraceableTransformer::class)
                ->setDecoratedService($serviceId)
                ->setArguments([
                    new Reference($decoratedServiceId.'.inner'),
                    $dataCollector,
                ])
            ;
        }

        // decorates ObjectToObjectMetadataFactory

        $serviceId = 'rekalogika.mapper.object_to_object_metadata_factory.cache';
        $container->register('debug.'.$serviceId, TraceableObjectToObjectMetadataFactory::class)
            ->setDecoratedService($serviceId)
            ->setArguments([
                new Reference('debug.'.$serviceId.'.inner'),
                $dataCollector,
            ])
        ;

        // decorates mapping factory

        $serviceId = 'rekalogika.mapper.mapping_factory';
        $container->register('debug.'.$serviceId, TraceableMappingFactory::class)
            ->setDecoratedService($serviceId, null, 50)
            ->setArguments([
                new Reference('debug.'.$serviceId.'.inner'),
                $dataCollector,
            ])
            ->addTag('kernel.reset', ['method' => 'reset'])
        ;
    }
}
