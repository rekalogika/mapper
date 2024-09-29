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

namespace Rekalogika\Mapper\DependencyInjection\CompilerPass;

use Rekalogika\Mapper\DependencyInjection\ConfiguratorUtil;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodExtraArgumentUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final readonly class PropertyMapperPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $propertyMapperResolver = $container
            ->getDefinition('rekalogika.mapper.property_mapper.resolver');

        $taggedServices = $container->findTaggedServiceIds('rekalogika.mapper.property_mapper');

        foreach ($taggedServices as $serviceId => $tags) {
            $serviceDefinition = $container->getDefinition($serviceId);
            $serviceClass = $serviceDefinition->getClass() ?? throw new InvalidArgumentException('Class is required');

            if (!class_exists($serviceClass)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" does not exist', $serviceClass));
            }

            /** @var array{sourceClass:class-string,targetClass:class-string,property:string,serviceId:string,method:string,hasExistingTarget:bool,extraArguments:array<string,int>} $tag */
            foreach ($tags as $tag) {
                $method = $tag['method'] ?? throw new InvalidArgumentException('Method is required');

                $hasExistingTargetParameter = ConfiguratorUtil::methodHasExistingTargetParameter($serviceClass, $method);

                $propertyMapperResolver->addMethodCall(
                    'addPropertyMapper',
                    [
                        '$sourceClass' => $tag['sourceClass'],
                        '$targetClass' => $tag['targetClass'],
                        '$property' => $tag['property'],
                        '$serviceId' => $serviceId,
                        '$method' => $method,
                        '$hasExistingTarget' => $hasExistingTargetParameter,
                        '$extraArguments' => ServiceMethodExtraArgumentUtil::getExtraArguments($serviceClass, $method, $hasExistingTargetParameter),
                    ],
                );
            }
        }
    }
}
