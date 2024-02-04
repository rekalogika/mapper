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

use Rekalogika\Mapper\DependencyInjection\CompilerPass\ObjectMapperPass;
use Rekalogika\Mapper\DependencyInjection\PropertyMapperPass;
use Rekalogika\Mapper\DependencyInjection\RemoveOptionalDefinitionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RekalogikaMapperBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RemoveOptionalDefinitionPass());
        $container->addCompilerPass(new PropertyMapperPass());
        $container->addCompilerPass(new ObjectMapperPass());
    }
}
