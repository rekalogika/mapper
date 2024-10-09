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
use Rekalogika\Mapper\Tests\PHPUnit\PHPUnitProfilerBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

date_default_timezone_set('UTC');

class TestKernel extends Kernel
{
    use MicroKernelTrait {
        registerContainerConfiguration as private baseRegisterContainerConfiguration;
    }

    private string $env;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        ?string $env = null,
        ?bool $debug = null,
        private readonly array $config = [],
    ) {
        $env ??= $_SERVER['APP_ENV'] ?? 'test';
        $debug ??= (bool) ($_SERVER['APP_DEBUG'] ?? true);

        /** @var string $env */
        $this->env = $env;

        parent::__construct($env, $debug);
    }

    #[\Override]
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new TwigBundle();
        yield new RekalogikaMapperBundle();
        yield new MonologBundle();

        if ($this->debug) {
            yield new WebProfilerBundle();
            yield new PHPUnitProfilerBundle();
            yield new DebugBundle();
        }
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MapperPass());

        parent::build($container);

    }

    #[\Override]
    public function getBuildDir(): string
    {
        return $this->getProjectDir() . '/var/build/' . $this->env;
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
