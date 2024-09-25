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
use Rekalogika\Mapper\RekalogikaMapperBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

date_default_timezone_set('UTC');

class TestKernel extends Kernel
{
    use MicroKernelTrait {
        registerContainerConfiguration as private baseRegisterContainerConfiguration;
    }
    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        string $env = 'test',
        bool $debug = true,
        private readonly array $config = [],
    ) {
        parent::__construct($env, $debug);
    }

    #[\Override]
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new RekalogikaMapperBundle();
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MapperPass());

        parent::build($container);

    }

    #[\Override]
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $this->baseRegisterContainerConfiguration($loader);

        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('rekalogika_mapper', $this->config);
        });
    }

    #[\Override]
    public function getProjectDir(): string
    {
        return __DIR__ . '/../../';
    }
}
