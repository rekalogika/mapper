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

namespace Rekalogika\Mapper\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PropertyMapperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $propertyMapperResolver = $container
            ->getDefinition('rekalogika.mapper.property_mapper.resolver');

        foreach ($container->findTaggedServiceIds('rekalogika.mapper.property_mapper') as $serviceId => $tags) {
            /** @var array<string,string> $tag */
            foreach ($tags as $tag) {
                $propertyMapperResolver->addMethodCall(
                    'addPropertyMapper',
                    [
                        $tag['sourceClass'],
                        $tag['targetClass'],
                        $tag['property'],
                        $serviceId,
                        $tag['method'],
                    ]
                );
            }
        }
    }
}
