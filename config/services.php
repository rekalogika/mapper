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
use Rekalogika\Mapper\Mapping\MappingFactory;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
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
use Rekalogika\Mapper\TypeStringHelper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services()

    # Property info

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

            ])

        ->set('rekalogika.mapper.cache.property_info')
            ->parent('cache.system')
            ->tag('cache.pool')

        ->set('rekalogika.mapper.property_info.cache', PropertyInfoCacheExtractor::class)
            ->decorate('rekalogika.mapper.property_info')
            ->args([
                service('rekalogika.mapper.property_info.cache.inner'),
                service('rekalogika.mapper.cache.property_info')
            ])

    # transformers

        ->set('rekalogika.mapper.transformer.scalar_to_scalar', ScalarToScalarTransformer::class)
            ->tag('rekalogika.mapper.transformer', ['priority' => -550])

        ->set('rekalogika.mapper.transformer.datetime', DateTimeTransformer::class)
            ->tag('rekalogika.mapper.transformer', ['priority' => -600])

        ->set('rekalogika.mapper.transformer.string_to_backed_enum', StringToBackedEnumTransformer::class)
            ->tag('rekalogika.mapper.transformer', ['priority' => -650])

        ->set('rekalogika.mapper.transformer.object_to_string', ObjectToStringTransformer::class)
            ->tag('rekalogika.mapper.transformer', ['priority' => -700])

        ->set('rekalogika.mapper.transformer.traversable_to_arrayaccess', TraversableToArrayAccessTransformer::class)
            ->tag('rekalogika.mapper.transformer', ['priority' => -750])

        ->set('rekalogika.mapper.transformer.traversable_to_traversable', TraversableToTraversableTransformer::class)
            ->tag('rekalogika.mapper.transformer', ['priority' => -800])

        ->set('rekalogika.mapper.transformer.object_to_array', ObjectToArrayTransformer::class)
            ->args([service(NormalizerInterface::class)])
            ->tag('rekalogika.mapper.transformer', ['priority' => -850])

        ->set('rekalogika.mapper.transformer.array_to_object', ArrayToObjectTransformer::class)
            ->args([service(DenormalizerInterface::class)])
            ->tag('rekalogika.mapper.transformer', ['priority' => -900])

        ->set('rekalogika.mapper.transformer.object_to_object', ObjectToObjectTransformer::class)
            ->args([
                '$propertyListExtractor' => service('rekalogika.mapper.property_info'),
                '$propertyTypeExtractor' => service('rekalogika.mapper.property_info'),
                '$propertyInitializableExtractor' => service('rekalogika.mapper.property_info'),
                '$propertyAccessExtractor' => service('rekalogika.mapper.property_info'),
                '$propertyAccessor' => service('property_accessor'),
            ])
            ->tag('rekalogika.mapper.transformer', ['priority' => -950])

        ->set('rekalogika.mapper.transformer.null', NullTransformer::class)
            ->tag('rekalogika.mapper.transformer', ['priority' => -1000])

    # other services

        ->set('rekalogika.mapper.type_string_helper', TypeStringHelper::class)

        ->set('rekalogika.mapper.mapping_factory', MappingFactory::class)
            ->args([tagged_iterator('rekalogika.mapper.transformer', 'key')])

        ->alias(MappingFactoryInterface::class, 'rekalogika.mapper.mapping_factory')

        ->set('rekalogika.mapper.main_transformer', MainTransformer::class)
            ->args([
                '$transformersLocator' => tagged_locator('rekalogika.mapper.transformer'),
                '$typeStringHelper' => service('rekalogika.mapper.type_string_helper'),
                '$mappingFactory' => service('rekalogika.mapper.mapping_factory'),
            ])

        ->set('rekalogika.mapper.mapper', Mapper::class)
            ->args([service('rekalogika.mapper.main_transformer')])

        ->alias(MapperInterface::class, 'rekalogika.mapper.mapper')

    # console command

        ->set('rekalogika.mapper.command.mapping', MappingCommand::class)
            ->args([service('rekalogika.mapper.mapping_factory')])
            ->tag('console.command')

        ->set('rekalogika.mapper.command.try', TryCommand::class)
            ->args([
                service('rekalogika.mapper.main_transformer'),
                service('rekalogika.mapper.type_string_helper'),
            ])
            ->tag('console.command')
    ;
};
