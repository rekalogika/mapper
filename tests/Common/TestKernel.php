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

namespace Rekalogika\Mapper\Tests\Common;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\RekalogikaMapperBundle;
use Rekalogika\Mapper\Transformer\Implementation\ArrayToObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ClassMethodTransformer;
use Rekalogika\Mapper\Transformer\Implementation\CopyTransformer;
use Rekalogika\Mapper\Transformer\Implementation\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\Implementation\NullTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectMapperTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToArrayTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\Implementation\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\Implementation\SymfonyUidTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToTraversableTransformer;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    /**
     * @param array<string,mixed> $config
     */
    public function __construct(private array $config = [])
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new RekalogikaMapperBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $confDir = $this->getProjectDir() . '/tests/Resources/';
        $loader->load($confDir . '*' . '.yaml', 'glob');

        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('rekalogika_mapper', $this->config);
        });
    }

    /**
     * @return iterable<int,string>
     */
    public static function getServiceIds(): iterable
    {
        yield MapperInterface::class;

        yield 'rekalogika.mapper.property_info';
        // yield 'rekalogika.mapper.cache.property_info';
        // yield 'rekalogika.mapper.property_info.cache';

        yield ScalarToScalarTransformer::class;
        yield ObjectMapperTransformer::class;
        yield DateTimeTransformer::class;
        yield StringToBackedEnumTransformer::class;
        yield SymfonyUidTransformer::class;
        yield ObjectToStringTransformer::class;
        yield TraversableToArrayAccessTransformer::class;
        yield TraversableToTraversableTransformer::class;
        yield ObjectToArrayTransformer::class;
        yield ArrayToObjectTransformer::class;
        yield ObjectToObjectTransformer::class;
        yield NullTransformer::class;
        yield CopyTransformer::class;

        yield 'rekalogika.mapper.mapping_factory';
        yield MappingFactoryInterface::class;
        yield 'rekalogika.mapper.mapping_factory.caching';

        yield 'rekalogika.mapper.type_resolver';
        yield 'rekalogika.mapper.type_resolver.caching';

        yield 'rekalogika.mapper.object_to_object_metadata_factory';
        yield 'rekalogika.mapper.cache.object_to_object_metadata_factory';
        yield 'rekalogika.mapper.object_to_object_metadata_factory.cache';

        yield 'rekalogika.mapper.array_like_metadata_factory';
        yield 'rekalogika.mapper.cache.array_like_metadata_factory';
        yield 'rekalogika.mapper.array_like_metadata_factory.cache';

        yield 'rekalogika.mapper.transformer_registry';
        yield 'rekalogika.mapper.cache.transformer_registry';
        yield 'rekalogika.mapper.transformer_registry.cache';

        yield 'rekalogika.mapper.sub_mapper.factory';

        yield 'rekalogika.mapper.property_mapper.resolver';

        yield 'rekalogika.mapper.object_mapper.table_factory';

        yield 'rekalogika.mapper.object_cache_factory';
        yield 'rekalogika.mapper.main_transformer';

        yield 'rekalogika.mapper.mapper';

        yield 'rekalogika.mapper.command.mapping';
        yield 'rekalogika.mapper.command.try';
        yield 'rekalogika.mapper.command.try_property';

        yield 'rekalogika.mapper.data_collector';

        yield 'rekalogika.mapper.eager_properties_resolver';
        yield 'rekalogika.mapper.eager_properties_resolver.heuristics';
        yield 'rekalogika.mapper.eager_properties_resolver.doctrine';
    }
}
