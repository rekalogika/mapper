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

use Rekalogika\Mapper\Command\MappingCommand;
use Rekalogika\Mapper\Command\TryCommand;
use Rekalogika\Mapper\MainTransformer;
use Rekalogika\Mapper\Mapper;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Mapping\CachingMappingFactory;
use Rekalogika\Mapper\Mapping\MappingFactory;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\ObjectCache\ObjectCacheFactory;
use Rekalogika\Mapper\Transformer\ArrayToObjectTransformer;
use Rekalogika\Mapper\Transformer\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\NullTransformer;
use Rekalogika\Mapper\Transformer\ObjectToArrayTransformer;
use Rekalogika\Mapper\Transformer\ObjectToObjectTransformer;
use Rekalogika\Mapper\Transformer\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\TraversableToTraversableTransformer;
use Rekalogika\Mapper\TypeResolver\TypeResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    # Property info

    $services
        ->set('rekalogika.mapper.property_info', PropertyInfoExtractor::class)
        ->args([
            '$listExtractors' => [
                service('property_info.reflection_extractor')
            ],
            '$typeExtractors' => [
                service('property_info.phpstan_extractor'),
                service('property_info.reflection_extractor'),
            ],
            '$accessExtractors' => [
                service('property_info.reflection_extractor')
            ],
            '$initializableExtractors' => [
                service('property_info.reflection_extractor')
            ],

        ]);

    $services
        ->set('rekalogika.mapper.cache.property_info')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services
        ->set('rekalogika.mapper.property_info.cache', PropertyInfoCacheExtractor::class)
        ->decorate('rekalogika.mapper.property_info')
        ->args([
            service('rekalogika.mapper.property_info.cache.inner'),
            service('rekalogika.mapper.cache.property_info')
        ]);

    # transformers

    $services
        ->set('rekalogika.mapper.transformer.scalar_to_scalar', ScalarToScalarTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -550]);

    $services
        ->set('rekalogika.mapper.transformer.datetime', DateTimeTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -600]);

    $services
        ->set('rekalogika.mapper.transformer.string_to_backed_enum', StringToBackedEnumTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -650]);

    $services
        ->set('rekalogika.mapper.transformer.object_to_string', ObjectToStringTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -700]);

    $services
        ->set('rekalogika.mapper.transformer.traversable_to_arrayaccess', TraversableToArrayAccessTransformer::class)
        ->args([
            '$objectCacheFactory' => service('rekalogika.mapper.object_cache_factory'),
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -750]);

    $services
        ->set('rekalogika.mapper.transformer.traversable_to_traversable', TraversableToTraversableTransformer::class)
        ->args([
            '$objectCacheFactory' => service('rekalogika.mapper.object_cache_factory'),
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -800]);

    $services
        ->set('rekalogika.mapper.transformer.object_to_array', ObjectToArrayTransformer::class)
        ->args([service(NormalizerInterface::class)])
        ->tag('rekalogika.mapper.transformer', ['priority' => -850]);

    $services
        ->set('rekalogika.mapper.transformer.array_to_object', ArrayToObjectTransformer::class)
        ->args([service(DenormalizerInterface::class)])
        ->tag('rekalogika.mapper.transformer', ['priority' => -900]);

    $services
        ->set('rekalogika.mapper.transformer.object_to_object', ObjectToObjectTransformer::class)
        ->args([
            '$propertyListExtractor' => service('rekalogika.mapper.property_info'),
            '$propertyTypeExtractor' => service('rekalogika.mapper.property_info'),
            '$propertyInitializableExtractor' => service('rekalogika.mapper.property_info'),
            '$propertyAccessExtractor' => service('rekalogika.mapper.property_info'),
            '$propertyAccessor' => service('property_accessor'),
            '$typeResolver' => service('rekalogika.mapper.type_resolver'),
            '$objectCacheFactory' => service('rekalogika.mapper.object_cache_factory'),
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -950]);

    $services
        ->set('rekalogika.mapper.transformer.null', NullTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -1000]);

    # mappingfactory

    $services
        ->set('rekalogika.mapper.mapping_factory', MappingFactory::class)
        ->args([
            tagged_iterator('rekalogika.mapper.transformer', 'key'),
            service('rekalogika.mapper.type_resolver')
        ]);

    $services
        ->alias(MappingFactoryInterface::class, 'rekalogika.mapper.mapping_factory');

    $services
        ->set('rekalogika.mapper.mapping_factory.caching', CachingMappingFactory::class)
        ->decorate('rekalogika.mapper.mapping_factory')
        ->args([
            service('rekalogika.mapper.mapping_factory.caching.inner'),
            service('kernel')
        ])
        ->tag('kernel.cache_warmer');

    # other services

    $services
        ->set('rekalogika.mapper.object_cache_factory', ObjectCacheFactory::class)
        ->args([service('rekalogika.mapper.type_resolver')]);

    $services
        ->set('rekalogika.mapper.type_resolver', TypeResolver::class);

    $services
        ->set('rekalogika.mapper.main_transformer', MainTransformer::class)
        ->args([
            '$transformersLocator' => tagged_locator('rekalogika.mapper.transformer'),
            '$typeResolver' => service('rekalogika.mapper.type_resolver'),
            '$mappingFactory' => service('rekalogika.mapper.mapping_factory'),
            '$objectCacheFactory' => service('rekalogika.mapper.object_cache_factory'),
        ]);

    $services
        ->set('rekalogika.mapper.mapper', Mapper::class)
        ->args([service('rekalogika.mapper.main_transformer')]);

    $services
        ->alias(MapperInterface::class, 'rekalogika.mapper.mapper');

    # console command

    $services
        ->set('rekalogika.mapper.command.mapping', MappingCommand::class)
        ->args([service('rekalogika.mapper.mapping_factory')])
        ->tag('console.command');

    $services
        ->set('rekalogika.mapper.command.try', TryCommand::class)
        ->args([
            service('rekalogika.mapper.main_transformer'),
            service('rekalogika.mapper.type_resolver'),
        ])
        ->tag('console.command');
};
