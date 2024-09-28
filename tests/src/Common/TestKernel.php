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
use Rekalogika\Mapper\Tests\PHPUnit\TestRequest;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Stopwatch\Stopwatch;

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
        yield new TwigBundle();
        yield new WebProfilerBundle();
        yield new RekalogikaMapperBundle();
        yield new DebugBundle();
        yield new MonologBundle();
        yield new PHPUnitProfilerBundle();
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MapperPass());

        parent::build($container);

    }

    // public function boot(): void
    // {
    //     parent::boot();

    //     dump(\debug_backtrace());
    // }

    // public function shutdown(): void
    // {
    //     $stopwatch = $this->getContainer()->get('debug.stopwatch');
    //     assert($stopwatch instanceof Stopwatch);

    //     $request = new TestRequest();

    //     $profiler = $this->getContainer()->get('profiler');
    //     assert($profiler instanceof Profiler);

    //     $profile = $profiler->collect($request, $request->getResponse(), null);
    //     assert($profile !== null);

    //     $profiler->saveProfile($profile);

    //     parent::shutdown();
    // }

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
