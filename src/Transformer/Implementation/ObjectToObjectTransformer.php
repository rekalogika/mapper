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

namespace Rekalogika\Mapper\Transformer\Implementation;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\MapperOptions;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodRunner;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\InstantiationFailureException;
use Rekalogika\Mapper\Transformer\Exception\NotAClassException;
use Rekalogika\Mapper\Transformer\Exception\UninitializedSourcePropertyException;
use Rekalogika\Mapper\Transformer\Exception\UnsupportedPropertyMappingException;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Model\AdderRemoverProxy;
use Rekalogika\Mapper\Transformer\Model\ConstructorArguments;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Transformer\Util\ReaderWriter;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;

final class ObjectToObjectTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    private ReaderWriter $readerWriter;

    public function __construct(
        private ObjectToObjectMetadataFactoryInterface $objectToObjectMetadataFactory,
        private ContainerInterface $propertyMapperLocator,
        private SubMapperFactoryInterface $subMapperFactory,
        private ProxyFactoryInterface $proxyFactory,
        PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->readerWriter = new ReaderWriter($propertyAccessor);
    }

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // verify source

        if (!\is_object($source)) {
            throw new InvalidArgumentException(\sprintf('The source must be an object, "%s" given.', get_debug_type($source)), context: $context);
        }

        $sourceType = TypeGuesser::guessTypeFromVariable($source);
        $sourceClass = $sourceType->getClassName();

        if (null === $sourceClass || !class_exists($sourceClass)) {
            throw new InvalidArgumentException("Cannot get the class name for the source type.", context: $context);
        }

        // verify target

        $targetClass = $targetType->getClassName();

        if (null === $targetClass) {
            throw new InvalidArgumentException("Cannot get the class name for the target type.", context: $context);
        }

        if (!class_exists($targetClass) && !interface_exists($targetClass)) {
            throw new NotAClassException($targetClass, context: $context);
        }

        // if sourceType and targetType are the same, just return the source

        if (null === $target && TypeCheck::isSomewhatIdentical($sourceType, $targetType) && !$source instanceof \stdClass) {
            return $source;
        }

        // get the object to object mapping metadata

        $objectToObjectMetadata = $this->objectToObjectMetadataFactory
            ->createObjectToObjectMetadata($sourceClass, $targetClass);

        // disregard target if target is read only or target value reading is
        // disabled

        if (
            $objectToObjectMetadata->isTargetReadOnly()
            || $context(MapperOptions::class)?->readTargetValue !== true
        ) {
            $target = null;
        }

        // initialize target if target is null

        if (null === $target) {
            $canUseTargetProxy = $objectToObjectMetadata->canUseTargetProxy()
                && $context(MapperOptions::class)?->lazyLoading;

            if ($canUseTargetProxy) {
                $target = $this->instantiateTargetProxy(
                    source: $source,
                    objectToObjectMetadata: $objectToObjectMetadata,
                    context: $context,
                );
            } else {
                $target = $this->instantiateRealTarget(
                    source: $source,
                    objectToObjectMetadata: $objectToObjectMetadata,
                    context: $context,
                );
            }
        } else {
            $canUseTargetProxy = false;

            if (!\is_object($target)) {
                throw new InvalidArgumentException(\sprintf('The target must be an object, "%s" given.', get_debug_type($target)), context: $context);
            }
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
                $objectToObjectMetadata->sourceAllowsDynamicProperties()
                && $objectToObjectMetadata->targetAllowsDynamicProperties()
            ) {
                $this->mapDynamicProperties(
                    source: $source,
                    target: $target,
                    objectToObjectMetadata: $objectToObjectMetadata,
                    context: $context,
                );
            }

            $this->readSourceAndWriteTarget(
                source: $source,
                target: $target,
                propertyMappings: $objectToObjectMetadata->getPropertyMappings(),
                context: $context,
            );
        }

        return $target;
    }

    private function instantiateRealTarget(
        object $source,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context,
    ): object {
        $targetClass = $objectToObjectMetadata->getTargetClass();

        // check if class is valid & instantiable

        if (!$objectToObjectMetadata->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        $constructorArguments = $this->generateConstructorArguments(
            source: $source,
            objectToObjectMetadata: $objectToObjectMetadata,
            context: $context,
        );

        try {
            $reflectionClass = new \ReflectionClass($targetClass);

            return $reflectionClass
                ->newInstanceArgs($constructorArguments->getArguments());
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

    private function instantiateTargetProxy(
        object $source,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context,
    ): object {
        $targetClass = $objectToObjectMetadata->getTargetClass();

        // check if class is valid & instantiable

        if (!$objectToObjectMetadata->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        // create proxy initializer. this initializer will be executed when the
        // proxy is first accessed

        $initializer = function (object $target) use (
            $source,
            $objectToObjectMetadata,
            $context,
        ): void {
            // if the constructor is lazy, run it here

            if (!$objectToObjectMetadata->constructorIsEager()) {
                $target = $this->runConstructorManually(
                    source: $source,
                    target: $target,
                    objectToObjectMetadata: $objectToObjectMetadata,
                    context: $context,
                );
            }

            // map lazy properties

            $this->readSourceAndWriteTarget(
                source: $source,
                target: $target,
                propertyMappings: $objectToObjectMetadata->getLazyPropertyMappings(),
                context: $context,
            );
        };

        // instantiate the proxy

        $target = $this->proxyFactory->createProxy(
            $targetClass,
            $initializer,
            $objectToObjectMetadata->getTargetProxySkippedProperties(),
        );

        // if the constructor is eager, run it here

        if ($objectToObjectMetadata->constructorIsEager()) {
            $target = $this->runConstructorManually(
                source: $source,
                target: $target,
                objectToObjectMetadata: $objectToObjectMetadata,
                context: $context,
            );
        }

        // map eager properties

        $this->readSourceAndWriteTarget(
            source: $source,
            target: $target,
            propertyMappings: $objectToObjectMetadata->getEagerPropertyMappings(),
            context: $context,
        );

        return $target;
    }

    private function runConstructorManually(
        object $source,
        object $target,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context,
    ): object {
        if (!method_exists($target, '__construct')) {
            return $target;
        }

        $constructorArguments = $this->generateConstructorArguments(
            source: $source,
            objectToObjectMetadata: $objectToObjectMetadata,
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

        return $target;
    }

    private function generateConstructorArguments(
        object $source,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context,
    ): ConstructorArguments {
        $propertyMappings = $objectToObjectMetadata->getConstructorPropertyMappings();

        $constructorArguments = new ConstructorArguments();

        foreach ($propertyMappings as $propertyMapping) {
            try {
                /** @var mixed $targetPropertyValue */
                [$targetPropertyValue,] = $this->transformValue(
                    propertyMapping: $propertyMapping,
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

                    $constructorArguments->addVariadicArgument($targetPropertyValue);
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

        return $constructorArguments;
    }

    /**
     * @param array<int,PropertyMapping> $propertyMappings
     */
    private function readSourceAndWriteTarget(
        object $source,
        object $target,
        array $propertyMappings,
        Context $context,
    ): void {
        foreach ($propertyMappings as $propertyMapping) {
            $this->readSourcePropertyAndWriteTargetProperty(
                source: $source,
                target: $target,
                propertyMapping: $propertyMapping,
                context: $context,
            );
        }
    }

    private function readSourcePropertyAndWriteTargetProperty(
        object $source,
        object $target,
        PropertyMapping $propertyMapping,
        Context $context,
    ): void {
        try {
            /** @var mixed $targetPropertyValue */
            [$targetPropertyValue, $isChanged] = $this->transformValue(
                propertyMapping: $propertyMapping,
                source: $source,
                target: $target,
                mandatory: false,
                context: $context,
            );
        } catch (UninitializedSourcePropertyException | UnsupportedPropertyMappingException) {
            return;
        }

        // write

        if ($isChanged) {
            $this->readerWriter->writeTargetProperty(
                target: $target,
                propertyMapping: $propertyMapping,
                value: $targetPropertyValue,
                context: $context,
            );
        }
    }

    /**
     * @param object|null $target Target is null if the transformation is for a
     * constructor argument
     * @return array{mixed,bool} The target value after transformation and whether the value differs from before transformation
     */
    private function transformValue(
        PropertyMapping $propertyMapping,
        object $source,
        ?object $target,
        bool $mandatory,
        Context $context,
    ): mixed {
        // if a custom property mapper is set, then use it

        if (($serviceMethodSpecification = $propertyMapping->getPropertyMapper()) !== null) {
            $serviceMethodRunner = ServiceMethodRunner::create(
                serviceLocator: $this->propertyMapperLocator,
                mainTransformer: $this->getMainTransformer(),
                subMapperFactory: $this->subMapperFactory,
            );

            /** @var mixed */
            $result = $serviceMethodRunner->run(
                serviceMethodSpecification: $serviceMethodSpecification,
                source: $source,
                targetType: null,
                context: $context,
            );

            return [$result, true];
        }

        // if source property name is null, continue. there is nothing to
        // transform

        $sourceProperty = $propertyMapping->getSourceProperty();

        if ($sourceProperty === null) {
            throw new UnsupportedPropertyMappingException();
        }

        // get the value of the source property

        try {
            /** @var mixed */
            $sourcePropertyValue = $this->readerWriter
                ->readSourceProperty($source, $propertyMapping, $context);
        } catch (UninitializedSourcePropertyException $e) {
            if (!$mandatory) {
                throw $e;
            }

            $sourcePropertyValue = null;
        }

        // short circuit. optimization for transformation between scalar and
        // null, so that we don't have to go through the main transformer for
        // this common task.

        if ($context(MapperOptions::class)?->objectToObjectScalarShortCircuit === true) {
            // if source is null & target accepts null, we set the
            // target to null

            if ($propertyMapping->targetCanAcceptNull() && $sourcePropertyValue === null) {
                return [null, true];
            }

            // if the the source is null or scalar, and the target is a scalar

            $targetScalarType = $propertyMapping->getTargetScalarType();

            if ($targetScalarType !== null) {
                if ($sourcePropertyValue === null) {
                    $result = match ($targetScalarType) {
                        'int' => 0,
                        'float' => 0.0,
                        'string' => '',
                        'bool' => false,
                        'null' => null,
                    };

                    return [$result, true];
                } elseif (\is_scalar($sourcePropertyValue)) {
                    $result = match ($targetScalarType) {
                        'int' => (int) $sourcePropertyValue,
                        'float' => (float) $sourcePropertyValue,
                        'string' => (string) $sourcePropertyValue,
                        'bool' => (bool) $sourcePropertyValue,
                        'null' => null,
                    };

                    return [$result, true];
                }
            }
        }

        // get the value of the target property if the target is an object and
        // target value reading is enabled

        if (
            \is_object($target)
            && $context(MapperOptions::class)?->readTargetValue
        ) {
            // if this is for a property mapping, not a constructor argument

            /** @var mixed */
            $targetPropertyValue = $this->readerWriter->readTargetProperty(
                $target,
                $propertyMapping,
                $context,
            );
        } else {
            // if this is for a constructor argument, we don't have an existing
            // value

            $targetPropertyValue = null;
        }

        // if we get an AdderRemoverProxy, change the target type

        $targetTypes = $propertyMapping->getTargetTypes();

        if ($targetPropertyValue instanceof AdderRemoverProxy) {
            $key = $targetTypes[0]->getCollectionKeyTypes();
            $value = $targetTypes[0]->getCollectionValueTypes();

            $targetTypes = [
                TypeFactory::objectWithKeyValue(
                    \ArrayAccess::class,
                    $key[0],
                    $value[0],
                ),
            ];
        }

        // guess source type, and get the compatible type from metadata, so
        // we can preserve generics information

        $guessedSourceType = TypeGuesser::guessTypeFromVariable($sourcePropertyValue);
        $sourceType = $propertyMapping->getCompatibleSourceType($guessedSourceType)
            ?? $guessedSourceType;

        // add attributes to context

        $sourceAttributes = $propertyMapping->getSourceAttributes();
        $context = $context->with($sourceAttributes);

        $targetAttributes = $propertyMapping->getTargetAttributes();
        $context = $context->with($targetAttributes);

        // transform the value

        /** @var mixed */
        $originalTargetPropertyValue = $targetPropertyValue;

        /** @var mixed */
        $targetPropertyValue = $this->getMainTransformer()->transform(
            source: $sourcePropertyValue,
            target: $targetPropertyValue,
            sourceType: $sourceType,
            targetTypes: $targetTypes,
            context: $context,
            path: $propertyMapping->getTargetProperty(),
        );

        return [$targetPropertyValue, $targetPropertyValue !== $originalTargetPropertyValue];
    }

    private function mapDynamicProperties(
        object $source,
        object $target,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context,
    ): void {
        $sourceProperties = $objectToObjectMetadata->getSourceProperties();

        /** @var mixed $sourcePropertyValue */
        foreach (get_object_vars($source) as $sourceProperty => $sourcePropertyValue) {
            if (!\in_array($sourceProperty, $sourceProperties, true)) {
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
                        $targetPropertyValue = $this->getMainTransformer()->transform(
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

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object(), true);
    }
}
