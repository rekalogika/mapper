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

use Rekalogika\Mapper\RekalogikaMapperBundle;
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
        yield 'rekalogika.mapper.transformer.scalar_to_scalar';
        yield 'rekalogika.mapper.transformer.datetime';
        yield 'rekalogika.mapper.transformer.string_to_backed_enum';
        yield 'rekalogika.mapper.transformer.object_to_string';
        yield 'rekalogika.mapper.transformer.traversable_to_arrayaccess';
        yield 'rekalogika.mapper.transformer.traversable_to_traversable';
        yield 'rekalogika.mapper.transformer.object_to_array';
        yield 'rekalogika.mapper.transformer.array_to_object';
        yield 'rekalogika.mapper.transformer.object_to_object';
        yield 'rekalogika.mapper.transformer.null';
        yield 'rekalogika.mapper.type_resolver';
        yield 'rekalogika.mapper.mapping_factory';
        yield 'rekalogika.mapper.main_transformer';
        yield 'rekalogika.mapper.mapper';
        yield 'rekalogika.mapper.command.mapping';
        yield 'rekalogika.mapper.command.try';
    }
}
