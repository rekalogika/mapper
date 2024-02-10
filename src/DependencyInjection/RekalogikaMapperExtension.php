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

use Rekalogika\Mapper\Attribute\AsObjectMapper;
use Rekalogika\Mapper\Attribute\AsPropertyMapper;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Tests\Common\TestKernel;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class RekalogikaMapperExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        // load services

        $loader->load('services.php');

        // load service configuration for test environment

        $env = $container->getParameter('kernel.environment');
        $debug = (bool) $container->getParameter('kernel.debug');

        if ($env === 'test' && class_exists(TestKernel::class)) {
            $loader->import('tests.php');
        }

        if ($debug) {
            $loader->load('debug.php');
        }

        // autoconfiguration

        $container->registerForAutoconfiguration(TransformerInterface::class)
            ->addTag('rekalogika.mapper.transformer');

        $container->registerForAutoconfiguration(EagerPropertiesResolverInterface::class)
            ->addTag('rekalogika.mapper.eager_properties_resolver');

        $container->registerAttributeForAutoconfiguration(
            AsPropertyMapper::class,
            self::propertyMapperConfigurator(...)
        );

        $container->registerAttributeForAutoconfiguration(
            AsObjectMapper::class,
            self::objectMapperConfigurator(...)
        );
    }

    private static function propertyMapperConfigurator(
        ChildDefinition $definition,
        AsPropertyMapper $attribute,
        \ReflectionMethod $reflector,
    ): void {
        /** @var array{sourceClass:class-string,targetClass:class-string,method:string} */
        $tagAttributes = [];

        // get the AsPropertyMapper attribute attached to the class

        $classReflection = $reflector->getDeclaringClass();
        $classAttributeReflection = $classReflection
            ->getAttributes(AsPropertyMapper::class)[0] ?? null;

        // populate tag attributes from AsPropertyMapper attribute
        // attached to the class

        if ($classAttributeReflection !== null) {
            $classAttribute = $classAttributeReflection->newInstance();
            $tagAttributes['targetClass'] = $classAttribute->targetClass;

            if ($classAttribute->property !== null) {
                throw new LogicException(sprintf(
                    '"AsPropertyMapper" attribute attached to the class "%s" must not have "property" attribute.',
                    $classReflection->getName()
                ));
            }
        }

        // populate tag attributes from AsPropertyMapper attribute

        $tagAttributes['method'] = $reflector->getName();

        if ($attribute->property !== null) {
            $tagAttributes['property'] = $attribute->property;
        }

        if ($attribute->targetClass !== null) {
            $tagAttributes['targetClass'] = $attribute->targetClass;
        }

        // Use the class of the first argument of the method as the source class

        $parameters = $reflector->getParameters();
        $firstParameter = $parameters[0] ?? null;
        $type = $firstParameter?->getType();

        if ($type === null || !$type instanceof \ReflectionNamedType) {
            throw new LogicException(
                sprintf(
                    'Unable to determine the source class for property mapper service "%s", method "%s".',
                    $definition->getClass() ?? '?',
                    $reflector->getName()
                )
            );
        }

        $tagAttributes['sourceClass'] = $type->getName();

        // if the property is missing, assume it is the same as the
        // method name

        if (!isset($tagAttributes['property'])) {
            $name = $reflector->getName();
            // remove 'map' prefix if exists
            $name = preg_replace('/^map/', '', $name);
            if ($name === null) {
                throw new LogicException(
                    sprintf(
                        'Unable to determine the property name for property mapper service "%s", method "%s".',
                        $definition->getClass() ?? '?',
                        $reflector->getName()
                    )
                );
            }

            // convert to camel case
            $name = lcfirst($name);

            $tagAttributes['property'] = $name;
        }

        // if the sourceClass is not a class, throw an exception

        if (!class_exists($tagAttributes['sourceClass'])) {
            throw new \LogicException(
                sprintf(
                    'Source class "%s" for property mapper service "%s", method "%s" does not exist.',
                    $tagAttributes['sourceClass'],
                    $definition->getClass() ?? '?',
                    $reflector->getName()
                )
            );
        }

        // if the targetClass is still missing, throw an exception

        if (!isset($tagAttributes['targetClass'])) {
            throw new \LogicException(
                sprintf(
                    'Unable to determine the target class for property mapper service "%s", method "%s".',
                    $definition->getClass() ?? '?',
                    $reflector->getName()
                )
            );
        }

        // if the targetClass is not a class, throw an exception

        if (!class_exists($tagAttributes['targetClass'])) {
            throw new \LogicException(
                sprintf(
                    'Target class "%s" for property mapper service "%s", method "%s" does not exist.',
                    $tagAttributes['targetClass'],
                    $definition->getClass() ?? '?',
                    $reflector->getName()
                )
            );
        }

        // finally

        $definition->addTag('rekalogika.mapper.property_mapper', $tagAttributes);
    }

    private static function objectMapperConfigurator(
        ChildDefinition $definition,
        AsObjectMapper $attribute,
        \ReflectionMethod $reflector,
    ): void {
        /** @var array{sourceClass:class-string,targetClass:class-string} */
        $tagAttributes = [];

        // add the method

        $tagAttributes['method'] = $reflector->getName();

        // Use the class of the first argument of the method as the source class

        $parameters = $reflector->getParameters();
        $firstParameter = $parameters[0] ?? null;
        $type = $firstParameter?->getType();

        if ($type === null || !$type instanceof \ReflectionNamedType) {
            throw new LogicException(
                sprintf(
                    'Unable to determine the source class for property mapper service "%s", method "%s".',
                    $definition->getClass() ?? '?',
                    $reflector->getName()
                )
            );
        }

        $tagAttributes['sourceClass'] = $type->getName();

        // use the class of the return type as the target class

        $returnType = $reflector->getReturnType();

        if ($returnType === null || !$returnType instanceof \ReflectionNamedType) {
            throw new LogicException(
                sprintf(
                    'Unable to determine the target class for property mapper service "%s", method "%s".',
                    $definition->getClass() ?? '?',
                    $reflector->getName()
                )
            );
        }

        $tagAttributes['targetClass'] = $returnType->getName();

        // finally

        $definition->addTag('rekalogika.mapper.object_mapper', $tagAttributes);
    }
}
