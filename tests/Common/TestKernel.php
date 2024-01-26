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

use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\RekalogikaMapperBundle;
use Rekalogika\Mapper\Transformer\ArrayToObjectTransformer;
use Rekalogika\Mapper\Transformer\ClassMethodTransformer;
use Rekalogika\Mapper\Transformer\CopyTransformer;
use Rekalogika\Mapper\Transformer\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\InheritanceMapTransformer;
use Rekalogika\Mapper\Transformer\NullTransformer;
use Rekalogika\Mapper\Transformer\ObjectToArrayTransformer;
use Rekalogika\Mapper\Transformer\ObjectToObjectTransformer;
use Rekalogika\Mapper\Transformer\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\TraversableToTraversableTransformer;
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
        yield new RekalogikaMapperBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => [
                    'log' => true,
                ],
            ]);

            $container->loadFromExtension('rekalogika_mapper', $this->config);
        });
    }

    /**
     * @return iterable<int,string>
     */
    public static function getServiceIds(): iterable
    {
        yield 'rekalogika.mapper.property_info';
        yield 'rekalogika.mapper.cache.property_info';
        yield 'rekalogika.mapper.property_info.cache';

        yield ScalarToScalarTransformer::class;
        yield DateTimeTransformer::class;
        yield StringToBackedEnumTransformer::class;
        yield ClassMethodTransformer::class;
        yield ObjectToStringTransformer::class;
        yield InheritanceMapTransformer::class;
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
        yield 'rekalogika.mapper.transformer_registry';
        yield 'rekalogika.mapper.method_mapper.sub_mapper';
        yield 'rekalogika.mapper.object_cache_factory';
        yield 'rekalogika.mapper.mapper';
        yield 'rekalogika.mapper.command.mapping';
        yield 'rekalogika.mapper.command.try';
        yield 'rekalogika.mapper.command.try_property';
    }
}
