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
use Rekalogika\Mapper\CustomMapper\Implementation\ObjectMapperTableFactory;
use Rekalogika\Mapper\CustomMapper\Implementation\PropertyMapperResolver;
use Rekalogika\Mapper\MainTransformer\MainTransformer;
use Rekalogika\Mapper\Mapper;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Mapping\Implementation\CachingMappingFactory;
use Rekalogika\Mapper\Mapping\Implementation\MappingFactory;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\ObjectCache\Implementation\ObjectCacheFactory;
use Rekalogika\Mapper\SubMapper\Implementation\SubMapperFactory;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadataFactory;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\CachingArrayLikeMetadataFactory;
use Rekalogika\Mapper\Transformer\ArrayToObjectTransformer;
use Rekalogika\Mapper\Transformer\ClassMethodTransformer;
use Rekalogika\Mapper\Transformer\CopyTransformer;
use Rekalogika\Mapper\Transformer\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\InheritanceMapTransformer;
use Rekalogika\Mapper\Transformer\NullTransformer;
use Rekalogika\Mapper\Transformer\ObjectMapperTransformer;
use Rekalogika\Mapper\Transformer\ObjectToArrayTransformer;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\CachingObjectToObjectMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectTransformer;
use Rekalogika\Mapper\Transformer\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\SymfonyUidTransformer;
use Rekalogika\Mapper\Transformer\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\TraversableToTraversableTransformer;
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
        ->set(ScalarToScalarTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -300]);

    $services
        ->set(ObjectMapperTransformer::class)
        ->args([
            service('rekalogika.mapper.sub_mapper.factory'),
            tagged_locator('rekalogika.mapper.object_mapper'),
            service('rekalogika.mapper.object_mapper.table_factory'),
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -350]);

    $services
        ->set(DateTimeTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -400]);

    $services
        ->set(StringToBackedEnumTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -450]);

    $services
        ->set(ClassMethodTransformer::class)
        ->args([
            service('rekalogika.mapper.sub_mapper.factory'),
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -500]);

    $services
        ->set(SymfonyUidTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -550]);

    $services
        ->set(ObjectToStringTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -600]);

    $services
        ->set(InheritanceMapTransformer::class)
        ->tag('rekalogika.mapper.transformer', ['priority' => -650]);

    $services
        ->set(TraversableToArrayAccessTransformer::class)
        ->args([
            service('rekalogika.mapper.array_like_metadata_factory')
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -700]);

    $services
        ->set(TraversableToTraversableTransformer::class)
        ->args([
            service('rekalogika.mapper.array_like_metadata_factory')
        ])
        ->tag('rekalogika.mapper.transformer', ['priority' => -750]);

    $services
        ->set(ObjectToArrayTransformer::class)
        ->args([service(NormalizerInterface::class)])
        ->tag('rekalogika.mapper.transformer', ['priority' => -800]);

    $services
        ->set(ArrayToObjectTransformer::class)
        ->args([service(DenormalizerInterface::class)])
        ->tag('rekalogika.mapper.transformer', ['priority' => -850]);

    $services
        ->set(ObjectToObjectTransformer::class)
        ->args([
            '$objectToObjectMetadataFactory' => service('rekalogika.mapper.object_to_object_metadata_factory'),
            '$propertyMapperLocator' => tagged_locator('rekalogika.mapper.property_mapper'),
            '$subMapperFactory' => service('rekalogika.mapper.sub_mapper.factory'),
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
            service(PropertyWriteInfoExtractorInterface::class)
        ]);

    $services
        ->set('rekalogika.mapper.cache.object_to_object_metadata_factory')
        ->parent('cache.system')
        ->tag('cache.pool');

    $services
        ->set('rekalogika.mapper.object_to_object_metadata_factory.cache', CachingObjectToObjectMetadataFactory::class)
        ->decorate('rekalogika.mapper.object_to_object_metadata_factory')
        ->args([
            service('.inner'),
            service('rekalogika.mapper.cache.object_to_object_metadata_factory')
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
        ]);

    # property mapper

    $services
        ->set('rekalogika.mapper.property_mapper.resolver', PropertyMapperResolver::class);

    # object mapper

    $services
        ->set('rekalogika.mapper.object_mapper.table_factory', ObjectMapperTableFactory::class);

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
