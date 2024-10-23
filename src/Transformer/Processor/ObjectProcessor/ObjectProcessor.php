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

namespace Rekalogika\Mapper\Transformer\Processor\ObjectProcessor;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\ExtraTargetValues;
use Rekalogika\Mapper\Context\MapperOptions;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodRunner;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\ExtraTargetPropertyNotFoundException;
use Rekalogika\Mapper\Transformer\Exception\InstantiationFailureException;
use Rekalogika\Mapper\Transformer\Exception\UnableToReadException;
use Rekalogika\Mapper\Transformer\Exception\UnableToWriteException;
use Rekalogika\Mapper\Transformer\Exception\UninitializedSourcePropertyException;
use Rekalogika\Mapper\Transformer\Exception\UnsupportedPropertyMappingException;
use Rekalogika\Mapper\Transformer\Model\AdderRemoverProxy;
use Rekalogika\Mapper\Transformer\Model\ConstructorArguments;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMappingMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Rekalogika\Mapper\Transformer\Processor\ObjectProcessorInterface;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
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
        private LoggerInterface $logger,
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
                [$targetPropertyValue,] = $this->transformValue(
                    metadata: $propertyMapping,
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
                        $targetPropertyValue,
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

            $target = $this->readSourcePropertyAndWriteTargetProperty(
                metadata: $propertyMapping,
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

            $target = $this->writeTargetProperty(
                metadata: $propertyMapping,
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

    //
    // property processor
    //

    /**
     * @return object The target object after writing the property, can be of a
     * different instance but should be of the same class
     */
    private function readSourcePropertyAndWriteTargetProperty(
        PropertyMappingMetadata $metadata,
        object $source,
        object $target,
        Context $context,
    ): object {
        if (
            $metadata->getTargetReadMode() === ReadMode::None
            && $metadata->getTargetSetterWriteMode() === WriteMode::None
        ) {
            return $target;
        }

        try {
            /** @var mixed $targetPropertyValue */
            [$targetPropertyValue, $isChanged] = $this->transformValue(
                metadata: $metadata,
                source: $source,
                target: $target,
                mandatory: false,
                context: $context,
            );
        } catch (UninitializedSourcePropertyException | UnsupportedPropertyMappingException) {
            return $target;
        }

        // write

        if (
            $isChanged
            || $metadata->getTargetSetterWriteMode() === WriteMode::DynamicProperty
        ) {
            if ($targetPropertyValue instanceof AdderRemoverProxy) {
                $target = $targetPropertyValue->getHostObject();
            }

            return $this->writeTargetProperty(
                metadata: $metadata,
                target: $target,
                value: $targetPropertyValue,
                context: $context,
                silentOnError: false,
            );
        }

        return $target;
    }

    /**
     * @param object|null $target Target is null if the transformation is for a
     * constructor argument
     * @return array{mixed,bool} The target value after transformation and whether the value differs from before transformation
     */
    public function transformValue(
        PropertyMappingMetadata $metadata,
        object $source,
        ?object $target,
        bool $mandatory,
        Context $context,
    ): mixed {
        // if a custom property mapper is set, then use it

        if ($metadata->hasPropertyMapper()) {
            /** @psalm-suppress MixedReturnStatement */
            return $this->transformValueUsingPropertyMapper(
                metadata: $metadata,
                source: $source,
                target: $target,
                context: $context,
            );
        }

        // if source property name is null, continue. there is nothing to
        // transform

        $sourceProperty = $metadata->getSourceProperty();

        if ($sourceProperty === null) {
            throw new UnsupportedPropertyMappingException();
        }

        // get the value of the source property

        try {
            /** @var mixed */
            $sourcePropertyValue = $this->readSourceProperty(
                metadata: $metadata,
                source: $source,
                context: $context,
            );
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

            if ($metadata->targetCanAcceptNull() && $sourcePropertyValue === null) {
                return [null, true];
            }

            // if the the source is null or scalar, and the target is a scalar

            $targetScalarType = $metadata->getTargetScalarType();

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
            $targetPropertyValue = $this->readTargetProperty(
                metadata: $metadata,
                target: $target,
                context: $context,
            );
        } else {
            // if this is for a constructor argument, we don't have an existing
            // value

            $targetPropertyValue = null;
        }

        // if we get an AdderRemoverProxy, change the target type

        $targetTypes = $metadata->getTargetTypes();

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
        $sourceType = $metadata->getCompatibleSourceType($guessedSourceType)
            ?? $guessedSourceType;

        // add attributes to context

        $sourceAttributes = $metadata->getSourceAttributes();
        $context = $context->with($sourceAttributes);

        $targetAttributes = $metadata->getTargetAttributes();
        $context = $context->with($targetAttributes);

        // transform the value

        /** @var mixed */
        $originalTargetPropertyValue = $targetPropertyValue;

        /** @var mixed */
        $targetPropertyValue = $this->mainTransformer->transform(
            source: $sourcePropertyValue,
            target: $targetPropertyValue,
            sourceType: $sourceType,
            targetTypes: $targetTypes,
            context: $context,
            path: $metadata->getTargetProperty(),
        );

        return [
            $targetPropertyValue,
            $targetPropertyValue !== $originalTargetPropertyValue,
        ];
    }

    /**
     * @return array{mixed,bool} The target value after transformation and whether the value differs from before transformation
     */
    private function transformValueUsingPropertyMapper(
        PropertyMappingMetadata $metadata,
        object $source,
        ?object $target,
        Context $context,
    ): array {
        $serviceMethodSpecification = $metadata->getPropertyMapper();

        if ($serviceMethodSpecification === null) {
            throw new UnexpectedValueException('PropertyMapper is null', context: $context);
        }

        if ($target === null) {
            $targetPropertyValue = null;
        } else {
            /** @var mixed */
            $targetPropertyValue = $this->readTargetProperty(
                metadata: $metadata,
                target: $target,
                context: $context,
            );
        }

        $serviceMethodRunner = ServiceMethodRunner::create(
            serviceLocator: $this->propertyMapperLocator,
            mainTransformer: $this->mainTransformer,
            subMapperFactory: $this->subMapperFactory,
        );

        /** @var mixed */
        $result = $serviceMethodRunner->runPropertyMapper(
            serviceMethodSpecification: $serviceMethodSpecification,
            source: $source,
            target: $target,
            targetPropertyValue: $targetPropertyValue,
            targetType: null,
            context: $context,
        );

        return [$result, $result !== $targetPropertyValue];
    }

    /**
     * @throws UninitializedSourcePropertyException
     * @throws UnableToReadException
     */
    private function readSourceProperty(
        PropertyMappingMetadata $metadata,
        object $source,
        Context $context,
    ): mixed {
        $property = $metadata->getSourceProperty();

        if ($property === null) {
            return null;
        }

        if ($metadata->getSourceReadVisibility() !== Visibility::Public) {
            throw new UnableToReadException(
                source: $source,
                property: $property,
                context: $context,
            );
        }

        try {
            $accessorName = $metadata->getSourceReadName();
            $mode = $metadata->getSourceReadMode();

            if ($accessorName === null) {
                throw new UnexpectedValueException('AccessorName is null', context: $context);
            }

            if ($mode === ReadMode::Property) {
                return $source->{$accessorName};
            } elseif ($mode === ReadMode::Method) {
                /** @psalm-suppress MixedMethodCall */
                return $source->{$accessorName}();
            } elseif ($mode === ReadMode::PropertyPath) {
                return $this->propertyAccessor
                    ->getValue($source, $accessorName);
            } elseif ($mode === ReadMode::DynamicProperty) {
                $errorHandler = static function (
                    int $errno,
                    string $errstr,
                    string $errfile,
                    int $errline,
                ) use ($accessorName): bool {
                    if (str_starts_with($errstr, 'Undefined property')) {
                        restore_error_handler();
                        throw new UninitializedSourcePropertyException($accessorName);
                    }

                    return false;
                };

                set_error_handler($errorHandler);
                /** @var mixed */
                $result = $source->{$accessorName};
                restore_error_handler();

                return $result;
            }

            return null;
        } catch (\Error $e) {
            $message = $e->getMessage();

            if (
                str_contains($message, 'must not be accessed before initialization')
                || str_contains($message, 'Cannot access uninitialized non-nullable property')
            ) {
                throw new UninitializedSourcePropertyException($property);
            }

            throw new UnableToReadException(
                source: $source,
                property: $property,
                context: $context,
                previous: $e,
            );
        } catch (\BadMethodCallException) {
            throw new UninitializedSourcePropertyException($property);
        }
    }

    /**
     * @throws UnableToReadException
     */
    private function readTargetProperty(
        PropertyMappingMetadata $metadata,
        object $target,
        Context $context,
    ): mixed {
        if (
            $metadata->getTargetSetterWriteMode() === WriteMode::AdderRemover
            && $metadata->getTargetSetterWriteVisibility() === Visibility::Public
        ) {
            if (
                $metadata->getTargetRemoverWriteVisibility() === Visibility::Public
            ) {
                $removerMethodName = $metadata->getTargetRemoverWriteName();
            } else {
                $removerMethodName = null;
            }

            return new AdderRemoverProxy(
                hostObject: $target,
                getterMethodName: $metadata->getTargetReadName(),
                adderMethodName: $metadata->getTargetSetterWriteName(),
                removerMethodName: $removerMethodName,
            );
        }

        if ($metadata->getTargetReadVisibility() !== Visibility::Public) {
            return null;
        }

        try {
            $accessorName = $metadata->getTargetReadName();
            $readMode = $metadata->getTargetReadMode();

            if ($accessorName === null) {
                throw new UnexpectedValueException('AccessorName is null', context: $context);
            }

            if ($readMode === ReadMode::Property) {
                return $target->{$accessorName};
            } elseif ($readMode === ReadMode::Method) {
                /** @psalm-suppress MixedMethodCall */
                return $target->{$accessorName}();
            } elseif ($readMode === ReadMode::PropertyPath) {
                return $this->propertyAccessor
                    ->getValue($target, $accessorName);
            } elseif ($readMode === ReadMode::DynamicProperty) {
                return $target->{$accessorName} ?? null;
            }

            return null;
        } catch (\Error $e) {
            $message = $e->getMessage();

            if (
                str_contains($message, 'must not be accessed before initialization')
                || str_contains($message, 'Cannot access uninitialized non-nullable property')
            ) {
                return null;
            }

            throw new UnableToReadException(
                source: $target,
                property: $metadata->getTargetProperty(),
                context: $context,
                previous: $e,
            );
        }
    }

    /**
     * @throws UnableToWriteException
     */
    public function writeTargetProperty(
        PropertyMappingMetadata $metadata,
        object $target,
        mixed $value,
        Context $context,
        bool $silentOnError,
    ): object {
        $accessorName = $metadata->getTargetSetterWriteName();
        $writeMode = $metadata->getTargetSetterWriteMode();
        $visibility = $metadata->getTargetSetterWriteVisibility();

        if (
            $visibility !== Visibility::Public
            || $writeMode === WriteMode::None
        ) {
            if (!$silentOnError) {
                $this->logger->warning(
                    'Transformation of property "{property}" on target class "{class}" results in a different object instance from the original instance, but the new instance cannot be set on the target object. To fix the problem, you may 1. make the property public, 2. add a setter method for the property, or 3. add "#[Map(false)]" attribute on the property to skip the mapping.',
                    [
                        'property' => $metadata->getTargetProperty(),
                        'class' => get_debug_type($target),
                    ],
                );
            }

            return $target;
        }

        if ($accessorName === null) {
            throw new UnexpectedValueException('AccessorName is null', context: $context);
        }

        try {
            if ($writeMode === WriteMode::Property) {
                $target->{$accessorName} = $value;
            } elseif ($writeMode === WriteMode::Method) {
                if ($metadata->isTargetSetterVariadic()) {
                    if (!\is_array($value) && !$value instanceof \Traversable) {
                        $value = [$value];
                    }

                    /** @psalm-suppress MixedArgument */
                    $value = iterator_to_array($value);

                    /**
                     * @psalm-suppress MixedMethodCall
                     * @var mixed
                     */
                    $result = $target->{$accessorName}(...$value);
                } else {
                    /**
                     * @psalm-suppress MixedMethodCall
                     * @var mixed
                     */
                    $result = $target->{$accessorName}($value);
                }

                // if the setter returns the a value with the same type as the
                // target object, we assume that the setter method is a fluent
                // interface or an immutable setter, and we return the result

                if (
                    \is_object($result) && is_a($result, $target::class, true)
                ) {
                    return $result;
                }
            } elseif ($writeMode === WriteMode::AdderRemover) {
                // noop
            } elseif ($writeMode === WriteMode::PropertyPath) {
                // PropertyAccessor might modify the target object
                $temporaryTarget = $target;

                $this->propertyAccessor
                    ->setValue($temporaryTarget, $accessorName, $value);
            } elseif ($writeMode === WriteMode::DynamicProperty) {
                $target->{$accessorName} = $value;
            }
        } catch (\BadMethodCallException) {
            return $target;
        } catch (\Throwable $e) {
            throw new UnableToWriteException(
                target: $target,
                propertyName: $metadata->getTargetProperty(),
                context: $context,
                previous: $e,
            );
        }

        return $target;
    }
}
