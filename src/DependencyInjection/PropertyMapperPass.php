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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\CustomMapper\ServiceMethodSpecification;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PropertyMapperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $propertyMapperResolver = $container
            ->getDefinition('rekalogika.mapper.property_mapper.resolver');

        $taggedServices = $container->findTaggedServiceIds('rekalogika.mapper.property_mapper');

        foreach ($taggedServices as $serviceId => $tags) {
            $serviceDefinition = $container->getDefinition($serviceId);
            $serviceClass = $serviceDefinition->getClass() ?? throw new InvalidArgumentException('Class is required');

            /** @var array<string,string> $tag */
            foreach ($tags as $tag) {
                $method = $tag['method'] ?? throw new InvalidArgumentException('Method is required');

                $propertyMapperResolver->addMethodCall(
                    'addPropertyMapper',
                    [
                        $tag['sourceClass'],
                        $tag['targetClass'],
                        $tag['property'],
                        $serviceId,
                        $method,
                        self::getExtraArguments($serviceClass, $method),
                    ]
                );
            }
        }
    }

    /**
     * @param class-string $serviceClass
     * @return array<int,ServiceMethodSpecification::ARGUMENT_*>
     */
    private static function getExtraArguments(
        string $serviceClass,
        string $method
    ): array {
        $reflectionClass = new \ReflectionClass($serviceClass);
        $parameters = $reflectionClass->getMethod($method)->getParameters();
        // remove first element, which is always the source class
        array_shift($parameters);

        $extraArguments = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Extra arguments for property mapper "%s" in class "%s" must be type hinted.',
                        $method,
                        $serviceClass,
                    )
                );
            }

            $extraArguments[] = match ($type->getName()) {
                Context::class => ServiceMethodSpecification::ARGUMENT_CONTEXT,
                MainTransformerInterface::class => ServiceMethodSpecification::ARGUMENT_MAIN_TRANSFORMER,
                default => throw new InvalidArgumentException(
                    sprintf(
                        'Extra argument with type "%s" for property mapper "%s" in class "%s" is unsupported.',
                        $type->getName(),
                        $method,
                        $serviceClass,
                    )
                )
            };
        }

        return $extraArguments;
    }
}
