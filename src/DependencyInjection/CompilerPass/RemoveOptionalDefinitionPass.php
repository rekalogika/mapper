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

use Rekalogika\Mapper\Transformer\Implementation\SymfonyUidTransformer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @internal
 */
final readonly class RemoveOptionalDefinitionPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!class_exists(UuidFactory::class)) {
            $container->removeDefinition(SymfonyUidTransformer::class);
        }

        if (!$container->hasDefinition('doctrine')) {
            $container->removeDefinition('rekalogika.mapper.eager_properties_resolver.doctrine');
            $container->removeDefinition('rekalogika.mapper.proxy.generator.doctrine');
        }
    }
}
