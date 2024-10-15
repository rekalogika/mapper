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

namespace Rekalogika\Mapper\TransformerProcessor\ObjectProcessor;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\ExtraTargetValues;
use Rekalogika\Mapper\Context\MapperOptions;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\ExtraTargetPropertyNotFoundException;
use Rekalogika\Mapper\Transformer\Exception\InstantiationFailureException;
use Rekalogika\Mapper\Transformer\Exception\UninitializedSourcePropertyException;
use Rekalogika\Mapper\Transformer\Exception\UnsupportedPropertyMappingException;
use Rekalogika\Mapper\Transformer\Model\ConstructorArguments;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMappingMetadata;
use Rekalogika\Mapper\TransformerProcessor\ObjectProcessorInterface;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class ObjectProcessor implements ObjectProcessorInterface
{
    public function __construct(
        private ObjectToObjectMetadata $metadata,
        private MainTransformerInterface $mainTransformer,
        private ContainerInterface $propertyMapperLocator,
        private SubMapperFactoryInterface $subMapperFactory,
        private ProxyFactoryInterface $proxyFactory,
        private PropertyAccessorInterface $propertyAccessor,
        private PropertyProcessorFactoryInterface $propertyProcessorFactory,
    ) {}

    public function transform(
        object $source,
        ?object $target,
        Type $targetType,
        Context $context,
    ): object {
        // disregard target if target is read only or target value reading is
        // disabled

        if ($context(MapperOptions::class)?->readTargetValue !== true) {
            $target = null;
        }

        // get extra target values

        $extraTargetValues = $this->getExtraTargetValues($context);

        // initialize target if target is null

        if (null === $target) {
            $canUseTargetProxy = $this->metadata->canUseTargetProxy()
                && $context(MapperOptions::class)?->lazyLoading;

            if ($canUseTargetProxy) {
                $target = $this->instantiateTargetProxy(
                    source: $source,
                    extraTargetValues: $extraTargetValues,
                    context: $context,
                );

                $constructorArgumentNames = [];
            } else {
                [$target, $constructorArgumentNames] = $this->instantiateRealTarget(
                    source: $source,
                    extraTargetValues: $extraTargetValues,
                    context: $context,
                );
            }
        } else {
            $constructorArgumentNames = [];
            $canUseTargetProxy = false;
        }

        // save object to cache

        $context(ObjectCache::class)?->saveTarget(
            source: $source,
            targetType: $targetType,
            target: $target,
        );

        // map properties if it is not a proxy

        if (!$canUseTargetProxy) {
            // map dynamic properties if both are stdClass or allow dynamic
            // properties

            if (
                $this->metadata->sourceAllowsDynamicProperties()
                && $this->metadata->targetAllowsDynamicProperties()
            ) {
                $this->mapDynamicProperties(
                    source: $source,
                    target: $target,
                    context: $context,
                    exclude: $constructorArgumentNames,
                );
            }

            $target = $this->readSourceAndWriteTarget(
                source: $source,
                target: $target,
                propertyMappings: $this->getPropertyMappings(),
                extraTargetValues: $extraTargetValues,
                exclude: $constructorArgumentNames,
                context: $context,
            );
        }

        return $target;
    }

    //
    // property mappings getters
    //

    /**
     * @return array<string,PropertyMappingMetadata>
     */
    private function getPropertyMappings(): array
    {
        return $this->metadata->getPropertyMappings();
    }

    /**
     * @return array<string,PropertyMappingMetadata>
     */
    private function getLazyPropertyMappings(): array
    {
        return $this->metadata->getLazyPropertyMappings();
    }

    /**
     * @return array<string,PropertyMappingMetadata>
     */
    private function getEagerPropertyMappings(): array
    {
        return $this->metadata->getEagerPropertyMappings();
    }

    //
    // extra target values
    //

    /**
     * @return array<string,mixed>
     */
    private function getExtraTargetValues(Context $context): array
    {
        $extraTargetValues = $context(ExtraTargetValues::class)
            ?->getArgumentsForClass($this->metadata->getAllTargetClasses())
            ?? [];

        $allPropertyMappings = $this->metadata->getPropertyMappings();

        foreach (array_keys($extraTargetValues) as $property) {
            if (!isset($allPropertyMappings[$property])) {
                throw new ExtraTargetPropertyNotFoundException(
                    class: $this->metadata->getTargetClass(),
                    property: $property,
                    context: $context,
                );
            }
        }

        return $extraTargetValues;
    }

    //
    // instantiation
    //

    /**
     * @param array<string,mixed> $extraTargetValues
     * @return array{object,list<string>} the object & the list of constructor arguments
     */
    private function instantiateRealTarget(
        object $source,
        array $extraTargetValues,
        Context $context,
    ): array {
        $targetClass = $this->metadata->getTargetClass();

        // check if class is valid & instantiable

        if (!$this->metadata->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        $constructorArguments = $this->generateConstructorArguments(
            source: $source,
            extraTargetValues: $extraTargetValues,
            context: $context,
        );

        try {
            $reflectionClass = new \ReflectionClass($targetClass);

            $target = $reflectionClass
                ->newInstanceArgs($constructorArguments->getArguments());

            return [$target, $constructorArguments->getArgumentNames()];
        } catch (\TypeError | \ReflectionException $e) {
            throw new InstantiationFailureException(
                source: $source,
                targetClass: $targetClass,
                constructorArguments: $constructorArguments->getArguments(),
                unsetSourceProperties: $constructorArguments->getUnsetSourceProperties(),
                previous: $e,
                context: $context,
            );
        }
    }

    /**
     * @param array<string,mixed> $extraTargetValues
     */
    private function instantiateTargetProxy(
        object $source,
        array $extraTargetValues,
        Context $context,
    ): object {
        $targetClass = $this->metadata->getTargetClass();

        // check if class is valid & instantiable

        if (!$this->metadata->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        // create proxy initializer. this initializer will be executed when the
        // proxy is first accessed

        $constructorArgumentNames = [];

        $initializer = function (object $target) use (
            $source,
            $context,
            $extraTargetValues,
            &$constructorArgumentNames,
        ): void {
            // if the constructor is lazy, run it here

            if (!$this->metadata->constructorIsEager()) {
                $constructorArgumentNames = $this->runConstructorManually(
                    source: $source,
                    target: $target,
                    extraTargetValues: $extraTargetValues,
                    context: $context,
                );
            }

            // map lazy properties

            $this->readSourceAndWriteTarget(
                source: $source,
                target: $target,
                propertyMappings: $this->getLazyPropertyMappings(),
                extraTargetValues: $extraTargetValues,
                exclude: $constructorArgumentNames,
                context: $context,
            );
        };

        // instantiate the proxy

        $target = $this->proxyFactory->createProxy(
            class: $targetClass,
            initializer: $initializer,
            eagerProperties: $this->metadata->getTargetProxySkippedProperties(),
        );

        // if the constructor is eager, run it here

        if ($this->metadata->constructorIsEager()) {
            $constructorArgumentNames = $this->runConstructorManually(
                source: $source,
                target: $target,
                extraTargetValues: $extraTargetValues,
                context: $context,
            );
        }

        // map eager properties

        $target = $this->readSourceAndWriteTarget(
            source: $source,
            target: $target,
            propertyMappings: $this->getEagerPropertyMappings(),
            extraTargetValues: $extraTargetValues,
            exclude: $constructorArgumentNames,
            context: $context,
        );

        return $target;
    }

    /**
     * @param array<string,mixed> $extraTargetValues
     * @return list<string> the list of the constructor arguments
     */
    private function runConstructorManually(
        object $source,
        object $target,
        array $extraTargetValues,
        Context $context,
    ): array {
        if (!method_exists($target, '__construct')) {
            return [];
        }

        $constructorArguments = $this->generateConstructorArguments(
            source: $source,
            extraTargetValues: $extraTargetValues,
            context: $context,
        );

        $arguments = $constructorArguments->getArguments();

        try {
            /**
             * @psalm-suppress DirectConstructorCall
             * @psalm-suppress MixedMethodCall
             */
            $target->__construct(...$arguments);
        } catch (\TypeError | \ReflectionException $e) {
            throw new InstantiationFailureException(
                source: $source,
                targetClass: $target::class,
                constructorArguments: $constructorArguments->getArguments(),
                unsetSourceProperties: $constructorArguments->getUnsetSourceProperties(),
                previous: $e,
                context: $context,
            );
        }

        return $constructorArguments->getArgumentNames();
    }

    /**
     * @param array<string,mixed> $extraTargetValues
     */
    private function generateConstructorArguments(
        object $source,
        array $extraTargetValues,
        Context $context,
    ): ConstructorArguments {
        $constructorPropertyMappings = $this->metadata->getConstructorPropertyMappings();

        $constructorArguments = new ConstructorArguments();

        // add arguments from property mappings

        foreach ($constructorPropertyMappings as $propertyMapping) {
            try {
                /** @var mixed $targetPropertyValue */
                [$targetPropertyValue,] = $this->propertyProcessorFactory
                    ->getPropertyProcessor($propertyMapping)
                    ->transformValue(
                        source: $source,
                        target: null,
                        mandatory: $propertyMapping->isTargetConstructorMandatory(),
                        context: $context,
                    );

                if ($propertyMapping->isTargetConstructorVariadic()) {
                    if (
                        !\is_array($targetPropertyValue)
                        && !$targetPropertyValue instanceof \Traversable
                    ) {
                        $targetPropertyValue = [$targetPropertyValue];
                    }

                    $constructorArguments->addVariadicArgument(
                        $propertyMapping->getTargetProperty(),
                        $targetPropertyValue
                    );
                } else {
                    $constructorArguments->addArgument(
                        $propertyMapping->getTargetProperty(),
                        $targetPropertyValue,
                    );
                }
            } catch (UninitializedSourcePropertyException $e) {
                $sourceProperty = $e->getPropertyName();
                $constructorArguments->addUnsetSourceProperty($sourceProperty);

                continue;
            } catch (UnsupportedPropertyMappingException) {
                continue;
            }
        }

        // add arguments from extra target values

        /** @var mixed $value */
        foreach ($extraTargetValues as $property => $value) {
            // skip if there is no constructor property mapping for this
            if (!isset($constructorPropertyMappings[$property])) {
                continue;
            }

            $constructorArguments->addArgument($property, $value);
        }

        return $constructorArguments;
    }

    //
    // properties mapping
    //

    /**
     * @param array<string,PropertyMappingMetadata> $propertyMappings
     * @param array<string,mixed> $extraTargetValues
     * @param list<string> $exclude
     */
    private function readSourceAndWriteTarget(
        object $source,
        object $target,
        array $propertyMappings,
        array $extraTargetValues,
        array $exclude,
        Context $context,
    ): object {
        foreach ($propertyMappings as $propertyMapping) {
            $argumentName = $propertyMapping->getTargetProperty();

            if (\in_array($argumentName, $exclude, true)) {
                continue;
            }

            $target = $this->propertyProcessorFactory
                ->getPropertyProcessor($propertyMapping)
                ->readSourcePropertyAndWriteTargetProperty(
                    source: $source,
                    target: $target,
                    context: $context,
                );
        }

        // process extra target values

        /** @var mixed $value */
        foreach ($extraTargetValues as $property => $value) {
            if (!isset($propertyMappings[$property])) {
                continue;
            }

            if (\in_array($property, $exclude, true)) {
                continue;
            }

            $propertyMapping = $propertyMappings[$property];

            $target = $this->propertyProcessorFactory
                ->getPropertyProcessor($propertyMapping)
                ->writeTargetProperty(
                    target: $target,
                    value: $value,
                    context: $context,
                    silentOnError: true,
                );
        }

        return $target;
    }

    /**
     * @param list<string> $exclude
     */
    private function mapDynamicProperties(
        object $source,
        object $target,
        array $exclude,
        Context $context,
    ): void {
        $sourceProperties = $this->metadata->getSourceProperties();

        /** @var mixed $sourcePropertyValue */
        foreach (get_object_vars($source) as $sourceProperty => $sourcePropertyValue) {
            if (\in_array($sourceProperty, $sourceProperties, true)) {
                continue;
            }

            if (\in_array($sourceProperty, $exclude, true)) {
                continue;
            }

            try {
                if (isset($target->{$sourceProperty})) {
                    /** @psalm-suppress MixedAssignment */
                    $currentTargetPropertyValue = $target->{$sourceProperty};
                } else {
                    $currentTargetPropertyValue = null;
                }


                if (
                    $currentTargetPropertyValue === null
                    || \is_scalar($currentTargetPropertyValue)
                ) {
                    /** @psalm-suppress MixedAssignment */
                    $targetPropertyValue = $sourcePropertyValue;
                } else {
                    /** @var mixed */
                    $targetPropertyValue = $this->mainTransformer->transform(
                        source: $sourcePropertyValue,
                        target: $currentTargetPropertyValue,
                        sourceType: null,
                        targetTypes: [],
                        context: $context,
                        path: $sourceProperty,
                    );
                }
            } catch (\Throwable) {
                $targetPropertyValue = null;
            }

            $target->{$sourceProperty} = $targetPropertyValue;
        }
    }
}
