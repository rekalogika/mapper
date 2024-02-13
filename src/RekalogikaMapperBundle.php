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

namespace Rekalogika\Mapper;

use Rekalogika\Mapper\DependencyInjection\CompilerPass\DebugPass;
use Rekalogika\Mapper\DependencyInjection\CompilerPass\ObjectMapperPass;
use Rekalogika\Mapper\DependencyInjection\CompilerPass\PropertyMapperPass;
use Rekalogika\Mapper\DependencyInjection\CompilerPass\RemoveOptionalDefinitionPass;
use Rekalogika\Mapper\Proxy\ProxyAutoloaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @internal
 */
final class RekalogikaMapperBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RemoveOptionalDefinitionPass());
        $container->addCompilerPass(new PropertyMapperPass());
        $container->addCompilerPass(new ObjectMapperPass());

        if ((bool) $container->getParameter('kernel.debug')) {
            $container->addCompilerPass(new DebugPass());
        }
    }

    public function boot()
    {
        /** @var ProxyAutoloaderInterface */
        $autoloader = $this->container?->get('rekalogika.mapper.proxy_autoloader');

        $autoloader->registerAutoloader();
    }

    public function shutdown()
    {
        /** @var ProxyAutoloaderInterface */
        $autoloader = $this->container?->get('rekalogika.mapper.proxy_autoloader');

        $autoloader->unregisterAutoloader();
    }
}
