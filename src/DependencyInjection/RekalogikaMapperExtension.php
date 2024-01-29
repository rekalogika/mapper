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

use Rekalogika\Mapper\PropertyMapper\AsPropertyMapper;
use Rekalogika\Mapper\Tests\Common\TestKernel;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class RekalogikaMapperExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        // load services

        $loader->load('services.php');

        // load service configuration for test environment

        $env = $container->getParameter('kernel.environment');

        if ($env === 'test' && class_exists(TestKernel::class)) {
            $loader->import('tests.php');
        }

        // autoconfiguration

        $container->registerForAutoconfiguration(TransformerInterface::class)
            ->addTag('rekalogika.mapper.transformer');

        $container->registerAttributeForAutoconfiguration(
            AsPropertyMapper::class,
            static function (
                ChildDefinition $definition,
                AsPropertyMapper $attribute,
                \ReflectionMethod $reflector,
            ): void {
                $tagAttributes = \get_object_vars($attribute);
                $tagAttributes['method'] = $reflector->getName();

                if (
                    !isset($tagAttributes['sourceClass'])
                    || !isset($tagAttributes['targetClass'])
                ) {
                    $classReflection = $reflector->getDeclaringClass();
                    $classAttributeReflection = $classReflection
                        ->getAttributes(AsPropertyMapper::class)[0] ?? null;

                    if ($classAttributeReflection === null) {
                        throw new \LogicException(
                            sprintf(
                                'Trying to lookup "sourceClass" or "targetClass" from "AsPropertyMapper" attribute attached to the class "%s" because one or more parameters is not defined in the attribute attached to the method "%s", however the attribute is not found.',
                                $definition->getClass() ?? $classReflection->getName(),
                                $reflector->getName()
                            )
                        );
                    }

                    $classAttribute = $classAttributeReflection->newInstance();
                    $tagAttributes['sourceClass'] ??= $classAttribute->sourceClass;
                    $tagAttributes['targetClass'] ??= $classAttribute->targetClass;
                }

                if (!isset($tagAttributes['property'])) {
                    $tagAttributes['property'] = $reflector->getName();
                }

                if (!isset($tagAttributes['sourceClass'])) {
                    throw new \LogicException(
                        sprintf(
                            'Missing source class attribute for property mapper service "%s", method "%s".',
                            $definition->getClass() ?? '?',
                            $reflector->getName()
                        )
                    );
                }

                if (!isset($tagAttributes['targetClass'])) {
                    throw new \LogicException(
                        sprintf(
                            'Missing target class attribute for property mapper service "%s", method "%s".',
                            $definition->getClass() ?? '?',
                            $reflector->getName()
                        )
                    );
                }

                $definition->addTag('rekalogika.mapper.property_mapper', $tagAttributes);
            }
        );
    }
}
