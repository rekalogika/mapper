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
use Rekalogika\Mapper\Command\TryPropertyCommand;
use Rekalogika\Mapper\CustomMapper\Implementation\CachingObjectMapperResolver;
use Rekalogika\Mapper\CustomMapper\Implementation\ObjectMapperResolver;
use Rekalogika\Mapper\CustomMapper\Implementation\ObjectMapperTableFactory;
use Rekalogika\Mapper\CustomMapper\Implementation\PropertyMapperResolver;
use Rekalogika\Mapper\CustomMapper\Implementation\WarmableObjectMapperTableFactory;
use Rekalogika\Mapper\Implementation\Mapper;
use Rekalogika\Mapper\MainTransformer\Implementation\MainTransformer;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Mapping\Implementation\MappingCacheWarmer;
use Rekalogika\Mapper\Mapping\Implementation\MappingFactory;
use Rekalogika\Mapper\Mapping\Implementation\WarmableMappingFactory;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\ObjectCache\Implementation\ObjectCacheFactory;
use Rekalogika\Mapper\Proxy\Implementation\DoctrineProxyGenerator;
use Rekalogika\Mapper\Proxy\Implementation\DynamicPropertiesProxyGenerator;
use Rekalogika\Mapper\Proxy\Implementation\ProxyFactory;
use Rekalogika\Mapper\Proxy\Implementation\ProxyGenerator;
use Rekalogika\Mapper\Proxy\Implementation\ProxyRegistry;
use Rekalogika\Mapper\Proxy\ProxyGeneratorInterface;
use Rekalogika\Mapper\SubMapper\Implementation\SubMapperFactory;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Implementation\ArrayLikeMetadataFactory;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Implementation\CachingArrayLikeMetadataFactory;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\Implementation\ChainEagerPropertiesResolver;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\Implementation\DoctrineEagerPropertiesResolver;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\Implementation\HeuristicsEagerPropertiesResolver;
use Rekalogika\Mapper\Transformer\Implementation\ArrayObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ClassMethodTransformer;
use Rekalogika\Mapper\Transformer\Implementation\CopyTransformer;
use Rekalogika\Mapper\Transformer\Implementation\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\Implementation\NullToNullTransformer;
use Rekalogika\Mapper\Transformer\Implementation\NullTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectMapperTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\Implementation\PresetTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\Implementation\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\Implementation\SymfonyUidTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToTraversableTransformer;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\CachingObjectToObjectMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\ObjectToObjectMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\ProxyResolvingObjectToObjectMetadataFactory;
use Rekalogika\Mapper\TransformerRegistry\Implementation\CachingTransformerRegistry;
use Rekalogika\Mapper\TransformerRegistry\Implementation\TransformerRegistry;
use Rekalogika\Mapper\TypeResolver\Implementation\CachingTypeResolver;
use Rekalogika\Mapper\TypeResolver\Implementation\TypeResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
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

    # transformers

    $services
        ->set(NullToNullTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => 1000]);

    $services
        ->set(ScalarToScalarTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -350]);

    $services
        ->set(ObjectMapperTransformer::class)
        ->args([
            service('rekalogika.mapper.sub_mapper.factory'),
            tagged_locator('rekalogika.mapper.object_mapper'),
            service('rekalogika.mapper.object_mapper.table_factory'),
            service('rekalogika.mapper.object_mapper.resolver'),
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -400]);

    $services
        ->set(DateTimeTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -450]);

    $services
        ->set(StringToBackedEnumTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -500]);

    $services
        ->set(SymfonyUidTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -550]);

    $services
        ->set(ObjectToStringTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -600]);

    $services
        ->set(PresetTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -650]);

    $services
        // @phpstan-ignore-next-line
        ->set(ClassMethodTransformer::class)
        ->args([
            service('rekalogika.mapper.sub_mapper.factory'),
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -700]);

    $services
        ->set(TraversableToArrayAccessTransformer::class)
        ->args([
            service('rekalogika.mapper.array_like_metadata_factory')
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -750]);

    $services
        ->set(TraversableToTraversableTransformer::class)
        ->args([
            service('rekalogika.mapper.array_like_metadata_factory')
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -800]);

    $services
        ->set(ArrayObjectTransformer::class)
        ->args([service('rekalogika.mapper.transformer.array_object.object_to_object_transformer')])
        ->tag('rekalogika.mapper.transformer', ['priority' => -850]);

    $services
        ->set(ObjectToObjectTransformer::class)
        ->args([
            '$objectToObjectMetadataFactory' => service('rekalogika.mapper.object_to_object_metadata_factory'),
            '$propertyMapperLocator' => tagged_locator('rekalogika.mapper.property_mapper'),
            '$subMapperFactory' => service('rekalogika.mapper.sub_mapper.factory'),
            '$proxyFactory' => service('rekalogika.mapper.proxy.factory'),
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -900]);

    $services
        ->set(NullTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -950]);

    $services
        ->set(CopyTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -1000]);


    # mappingfactory

    $services
        ->alias(MappingFactoryInterface::class, 'rekalogika.mapper.mapping_factory');

    $services
        ->set('rekalogika.mapper.mapping_factory', MappingFactory::class)
        ->args([
            tagged_iterator('rekalogika.mapper.transformer', 'key'),
            service('rekalogika.mapper.type_resolver')
        ]);

    $services
        ->set('rekalogika.mapper.mapping_factory.caching', WarmableMappingFactory::class)
        ->decorate('rekalogika.mapper.mapping_factory', null, 100)
        ->args([
            service('.inner'),
            service('kernel')
        ]);

    # special instance of object to object transformer for ArrayObjectTransformer

    $services
        ->set(
            'rekalogika.mapper.transformer.array_object.object_to_object_transformer',
            ObjectToObjectTransformer::class
        )
        ->args([
            '$objectToObjectMetadataFactory' => service('rekalogika.mapper.object_to_object_metadata_factory'),
            '$propertyMapperLocator' => tagged_locator('rekalogika.mapper.property_mapper'),
            '$subMapperFactory' => service('rekalogika.mapper.sub_mapper.factory'),
            '$proxyFactory' => service('rekalogika.mapper.proxy.factory'),
        ]);

    # mapping cache warmer

    $services
        ->set('rekalogika.mapper.mapping_factory.warmer', MappingCacheWarmer::class)
        ->args([
            service('rekalogika.mapper.mapping_factory.caching'),
        ])
        ->tag('kernel.cache_warmer');

    # type resolver

    $services
        ->set('rekalogika.mapper.type_resolver', TypeResolver::class);

    $services
        ->set('rekalogika.mapper.type_resolver.caching', CachingTypeResolver::class)
        ->decorate('rekalogika.mapper.type_resolver')
        ->args([
            service('rekalogika.mapper.type_resolver.caching.inner'),
        ]);

    # object to object metadata factory

    $services
        ->set('rekalogika.mapper.object_to_object_metadata_factory', ObjectToObjectMetadataFactory::class)
        ->args([
            service('rekalogika.mapper.property_info'),
            service('rekalogika.mapper.property_info'),
            service('rekalogika.mapper.property_info'),
            service('rekalogika.mapper.property_mapper.resolver'),
            service(PropertyReadInfoExtractorInterface::class),
            service(PropertyWriteInfoExtractorInterface::class),
            service('rekalogika.mapper.eager_properties_resolver'),
            service('rekalogika.mapper.proxy.factory'),
            service('rekalogika.mapper.type_resolver'),
        ]);

    $services
        ->set('rekalogika.mapper.cache.object_to_object_metadata_factory')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services
        ->set('rekalogika.mapper.object_to_object_metadata_factory.cache', CachingObjectToObjectMetadataFactory::class)
        ->decorate('rekalogika.mapper.object_to_object_metadata_factory', null, 500)
        ->args([
            service('.inner'),
            service('rekalogika.mapper.cache.object_to_object_metadata_factory'),
            param('kernel.debug')
        ]);

    $services
        ->set('rekalogika.mapper.object_to_object_metadata_factory.proxy_resolving', ProxyResolvingObjectToObjectMetadataFactory::class)
        ->decorate('rekalogika.mapper.object_to_object_metadata_factory', null, -500)
        ->args([
            service('.inner'),
        ]);

    # array-like metadata factory

    $services
        ->set('rekalogika.mapper.array_like_metadata_factory', ArrayLikeMetadataFactory::class);

    $services
        ->set('rekalogika.mapper.cache.array_like_metadata_factory')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services
        ->set('rekalogika.mapper.array_like_metadata_factory.cache', CachingArrayLikeMetadataFactory::class)
        ->decorate('rekalogika.mapper.array_like_metadata_factory')
        ->args([
            service('.inner'),
            service('rekalogika.mapper.cache.array_like_metadata_factory')
        ]);

    # transformer registry

    $services
        ->set('rekalogika.mapper.transformer_registry', TransformerRegistry::class)
        ->args([
            '$transformersLocator' => tagged_locator('rekalogika.mapper.transformer'),
            '$typeResolver' => service('rekalogika.mapper.type_resolver'),
            '$mappingFactory' => service('rekalogika.mapper.mapping_factory'),
        ]);

    $services
        ->set('rekalogika.mapper.cache.transformer_registry')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services
        ->set('rekalogika.mapper.transformer_registry.cache', CachingTransformerRegistry::class)
        ->decorate('rekalogika.mapper.transformer_registry')
        ->args([
            service('.inner'),
            service('rekalogika.mapper.cache.transformer_registry')
        ]);

    # sub mapper

    $services
        ->set('rekalogika.mapper.sub_mapper.factory', SubMapperFactory::class)
        ->args([
            service('rekalogika.mapper.property_info'),
            service(PropertyAccessorInterface::class),
            service('rekalogika.mapper.proxy.factory'),
        ]);

    # property mapper

    $services
        ->set('rekalogika.mapper.property_mapper.resolver', PropertyMapperResolver::class);

    # object mapper table factory

    $services
        ->set('rekalogika.mapper.object_mapper.table_factory', ObjectMapperTableFactory::class);

    $services
        ->set('rekalogika.mapper.object_mapper.table_factory.warmed', WarmableObjectMapperTableFactory::class)
        ->decorate('rekalogika.mapper.object_mapper.table_factory')
        ->args([
            service('.inner'),
            service('kernel')
        ])
        ->tag('kernel.cache_warmer');

    # object mapper resolver

    $services
        ->set('rekalogika.mapper.object_mapper.resolver', ObjectMapperResolver::class)
        ->args([
            service('rekalogika.mapper.object_mapper.table_factory'),
        ]);

    $services
        ->set('rekalogika.mapper.cache.object_mapper_resolver')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services
        ->set('rekalogika.mapper.object_mapper.resolver.cache', CachingObjectMapperResolver::class)
        ->decorate('rekalogika.mapper.object_mapper.resolver')
        ->args([
            service('.inner'),
            service('rekalogika.mapper.cache.object_mapper_resolver')
        ]);

    # eager properties resolver

    $services
        ->alias(EagerPropertiesResolverInterface::class, 'rekalogika.mapper.eager_properties_resolver');

    $services
        ->set('rekalogika.mapper.eager_properties_resolver', ChainEagerPropertiesResolver::class)
        ->args([tagged_iterator('rekalogika.mapper.eager_properties_resolver')]);

    $services
        ->set('rekalogika.mapper.eager_properties_resolver.heuristics', HeuristicsEagerPropertiesResolver::class)
        ->tag('rekalogika.mapper.eager_properties_resolver', ['priority' => -1000]);

    $services
        ->set('rekalogika.mapper.eager_properties_resolver.doctrine', DoctrineEagerPropertiesResolver::class)
        ->args([service('doctrine')])
        ->tag('rekalogika.mapper.eager_properties_resolver', ['priority' => -500]);

    # proxy generator

    $services
        ->alias(ProxyGeneratorInterface::class, 'rekalogika.mapper.proxy.generator');

    $services
        ->set('rekalogika.mapper.proxy.generator', ProxyGenerator::class);

    $services
        ->set('rekalogika.mapper.proxy.generator.doctrine', DoctrineProxyGenerator::class)
        ->decorate('rekalogika.mapper.proxy.generator')
        ->args([
            service('.inner'),
            service('doctrine'),
        ]);

    $services
        ->set('rekalogika.mapper.proxy.generator.dynamic_properties', DynamicPropertiesProxyGenerator::class)
        ->decorate('rekalogika.mapper.proxy.generator')
        ->args([
            service('.inner'),
        ]);

    # proxy registry

    $services
        ->set('rekalogika.mapper.proxy.registry', ProxyRegistry::class)
        ->args([
            '$proxyDirectory' => '%kernel.cache_dir%/rekalogika-mapper/proxy',
        ]);

    $services
        ->alias('rekalogika.mapper.proxy_autoloader', 'rekalogika.mapper.proxy.registry')
        ->public();

    # proxy factory

    $services
        ->set('rekalogika.mapper.proxy.factory', ProxyFactory::class)
        ->args([
            service('rekalogika.mapper.proxy.registry'),
            service('rekalogika.mapper.proxy.generator'),
        ]);

    # other services

    $services
        ->set('rekalogika.mapper.object_cache_factory', ObjectCacheFactory::class)
        ->args([service('rekalogika.mapper.type_resolver')]);

    $services
        ->set('rekalogika.mapper.main_transformer', MainTransformer::class)
        ->args([
            '$objectCacheFactory' => service('rekalogika.mapper.object_cache_factory'),
            '$typeResolver' => service('rekalogika.mapper.type_resolver'),
            '$transformerRegistry' => service('rekalogika.mapper.transformer_registry'),
            '$debug' => param('kernel.debug'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

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
            service('rekalogika.mapper.transformer_registry'),
            service('rekalogika.mapper.type_resolver'),
        ])
        ->tag('console.command');

    $services
        ->set('rekalogika.mapper.command.try_property', TryPropertyCommand::class)
        ->args([
            service('rekalogika.mapper.transformer_registry'),
            service('rekalogika.mapper.type_resolver'),
            service('rekalogika.mapper.property_info'),
        ])
        ->tag('console.command');
};
