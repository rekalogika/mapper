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

namespace Rekalogika\Mapper\Transformer;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\MapperOptions;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodRunner;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\InstantiationFailureException;
use Rekalogika\Mapper\Transformer\Exception\NotAClassException;
use Rekalogika\Mapper\Transformer\Exception\UninitializedSourcePropertyException;
use Rekalogika\Mapper\Transformer\Exception\UnsupportedPropertyMappingException;
use Rekalogika\Mapper\Transformer\Model\AdderRemoverProxy;
use Rekalogika\Mapper\Transformer\Model\ConstructorArguments;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Rekalogika\Mapper\Transformer\Proxy\ProxyRegistryInterface;
use Rekalogika\Mapper\Transformer\Util\ReaderWriter;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\PropertyInfo\Type;

final class ObjectToObjectTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    private ReaderWriter $readerWriter;

    public function __construct(
        private ObjectToObjectMetadataFactoryInterface $objectToObjectMetadataFactory,
        private ContainerInterface $propertyMapperLocator,
        private SubMapperFactoryInterface $subMapperFactory,
        private ProxyRegistryInterface $proxyRegistry,
        ReaderWriter $readerWriter = null,
    ) {
        $this->readerWriter = $readerWriter ?? new ReaderWriter();
    }

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // verify source

        if (!is_object($source)) {
            throw new InvalidArgumentException(sprintf('The source must be an object, "%s" given.', get_debug_type($source)), context: $context);
        }

        $sourceType = TypeGuesser::guessTypeFromVariable($source);
        $sourceClass = $sourceType->getClassName();

        if (null === $sourceClass || !\class_exists($sourceClass)) {
            throw new InvalidArgumentException("Cannot get the class name for the source type.", context: $context);
        }

        // verify target

        $targetClass = $targetType->getClassName();

        if (null === $targetClass) {
            throw new InvalidArgumentException("Cannot get the class name for the target type.", context: $context);
        }

        if (!\class_exists($targetClass) && !\interface_exists($targetClass)) {
            throw new NotAClassException($targetClass, context: $context);
        }

        // if sourceType and targetType are the same, just return the source

        if (null === $target && TypeCheck::isSomewhatIdentical($sourceType, $targetType)) {
            return $source;
        }

        // get the object to object mapping metadata

        $objectToObjectMetadata = $this->objectToObjectMetadataFactory
            ->createObjectToObjectMetadata($sourceClass, $targetClass);

        // disregard target if target is read only or target value reading is
        // disabled

        if (
            $objectToObjectMetadata->isTargetReadOnly()
            || !$context(MapperOptions::class)?->enableTargetValueReading
        ) {
            $target = null;
        }

        // initialize target if target is null

        if (null === $target) {
            $canUseTargetProxy = $objectToObjectMetadata->canUseTargetProxy()
                && $context(MapperOptions::class)?->enableLazyLoading;

            if ($canUseTargetProxy) {
                $target = $this->instantiateTargetProxy(
                    source: $source,
                    objectToObjectMetadata: $objectToObjectMetadata,
                    context: $context
                );
            } else {
                $target = $this->instantiateRealTarget(
                    source: $source,
                    objectToObjectMetadata: $objectToObjectMetadata,
                    context: $context
                );
            }
        } else {
            $canUseTargetProxy = false;

            if (!is_object($target)) {
                throw new InvalidArgumentException(sprintf('The target must be an object, "%s" given.', get_debug_type($target)), context: $context);
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
            $this->readSourceAndWriteTarget(
                source: $source,
                target: $target,
                propertyMappings: $objectToObjectMetadata->getPropertyMappings(),
                context: $context
            );
        }

        return $target;
    }

    private function instantiateRealTarget(
        object $source,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context
    ): object {
        $targetClass = $objectToObjectMetadata->getTargetClass();

        // check if class is valid & instantiable

        if (!$objectToObjectMetadata->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        $constructorArguments = $this->generateConstructorArguments(
            source: $source,
            objectToObjectMetadata: $objectToObjectMetadata,
            context: $context
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
                context: $context
            );
        }
    }

    private function instantiateTargetProxy(
        object $source,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context
    ): object {
        $targetProxyClass = $objectToObjectMetadata->getTargetProxyClass();
        $targetClass = $objectToObjectMetadata->getTargetClass();

        // check if class is valid & instantiable

        if (!$objectToObjectMetadata->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        if ($targetProxyClass === null) {
            throw new LogicException('Target proxy class must not be null.', context: $context);
        }

        // if proxy class does not exist, create it

        if (!class_exists($targetProxyClass)) {
            $proxySpecification = $objectToObjectMetadata->getTargetProxySpecification();

            if ($proxySpecification === null) {
                throw new LogicException('Target proxy specification must not be null.', context: $context);
            }

            $this->proxyRegistry->registerProxy($proxySpecification);

            // @phpstan-ignore-next-line
            if (!class_exists($targetProxyClass)) {
                throw new LogicException(
                    sprintf('Unable to find target proxy class "%s".', $targetProxyClass),
                    context: $context
                );
            }
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
                    context: $context
                );
            }

            // map lazy properties

            $this->readSourceAndWriteTarget(
                source: $source,
                target: $target,
                propertyMappings: $objectToObjectMetadata->getLazyPropertyMappings(),
                context: $context
            );
        };

        // instantiate the proxy

        /**
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress MixedMethodCall
         * @var object
         */
        $target = $targetProxyClass::createLazyGhost(
            initializer: $initializer,
            skippedProperties: $objectToObjectMetadata->getTargetProxySkippedProperties()
        );

        // if the constructor is eager, run it here

        if ($objectToObjectMetadata->constructorIsEager()) {
            $target = $this->runConstructorManually(
                source: $source,
                target: $target,
                objectToObjectMetadata: $objectToObjectMetadata,
                context: $context
            );
        }

        // map eager properties

        $this->readSourceAndWriteTarget(
            source: $source,
            target: $target,
            propertyMappings: $objectToObjectMetadata->getEagerPropertyMappings(),
            context: $context
        );

        return $target;
    }

    private function runConstructorManually(
        object $source,
        object $target,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context
    ): object {
        if (!\method_exists($target, '__construct')) {
            return $target;
        }

        $constructorArguments = $this->generateConstructorArguments(
            source: $source,
            objectToObjectMetadata: $objectToObjectMetadata,
            context: $context
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
                context: $context
            );
        }

        return $target;
    }

    private function generateConstructorArguments(
        object $source,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context
    ): ConstructorArguments {
        $propertyMappings = $objectToObjectMetadata->getConstructorPropertyMappings();

        $constructorArguments = new ConstructorArguments();

        foreach ($propertyMappings as $propertyMapping) {
            try {
                /** @var mixed */
                $targetPropertyValue = $this->transformValue(
                    propertyMapping: $propertyMapping,
                    source: $source,
                    target: null,
                    context: $context
                );

                $constructorArguments->addArgument(
                    $propertyMapping->getTargetProperty(),
                    $targetPropertyValue
                );
            } catch (UninitializedSourcePropertyException $e) {
                $sourceProperty = $e->getPropertyName();
                $constructorArguments->addUnsetSourceProperty($sourceProperty);

                continue;
            } catch (UnsupportedPropertyMappingException $e) {
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
        Context $context
    ): void {
        foreach ($propertyMappings as $propertyMapping) {
            $this->readSourcePropertyAndWriteTargetProperty(
                source: $source,
                target: $target,
                propertyMapping: $propertyMapping,
                context: $context
            );
        }
    }

    private function readSourcePropertyAndWriteTargetProperty(
        object $source,
        object $target,
        PropertyMapping $propertyMapping,
        Context $context
    ): void {
        $targetWriteMode = $propertyMapping->getTargetWriteMode();
        $targetWriteVisibility = $propertyMapping->getTargetWriteVisibility();

        if (
            $targetWriteMode !== WriteMode::Method
            && $targetWriteMode !== WriteMode::Property
            && $targetWriteMode !== WriteMode::AdderRemover
        ) {
            return;
        }

        if ($targetWriteVisibility !== Visibility::Public) {
            return;
        }

        try {
            /** @var mixed */
            $targetPropertyValue = $this->transformValue(
                propertyMapping: $propertyMapping,
                source: $source,
                target: $target,
                context: $context
            );
        } catch (UninitializedSourcePropertyException $e) {
            return;
        } catch (UnsupportedPropertyMappingException $e) {
            return;
        }

        // write

        $this->readerWriter->writeTargetProperty(
            target: $target,
            propertyMapping: $propertyMapping,
            value: $targetPropertyValue,
            context: $context
        );
    }

    /**
     * @param object|null $target Target is null if the transformation is for a
     * constructor argument
     */
    private function transformValue(
        PropertyMapping $propertyMapping,
        object $source,
        ?object $target,
        Context $context
    ): mixed {
        // if a custom property mapper is set, then use it

        if ($serviceMethodSpecification = $propertyMapping->getPropertyMapper()) {
            $serviceMethodRunner = ServiceMethodRunner::create(
                serviceLocator: $this->propertyMapperLocator,
                mainTransformer: $this->getMainTransformer(),
                subMapperFactory: $this->subMapperFactory
            );

            return $serviceMethodRunner->run(
                serviceMethodSpecification: $serviceMethodSpecification,
                source: $source,
                targetType: null,
                context: $context
            );
        }

        // if source property name is null, continue. there is nothing to
        // transform

        $sourceProperty = $propertyMapping->getSourceProperty();

        if ($sourceProperty === null) {
            throw new UnsupportedPropertyMappingException();
        }

        // get the value of the source property

        /** @var mixed */
        $sourcePropertyValue = $this->readerWriter
            ->readSourceProperty($source, $propertyMapping, $context);

        // short circuit if the the source is null or scalar, and the target
        // is a scalar, so we don't have to delegate to the main transformer

        $targetScalarType = $propertyMapping->getTargetScalarType();

        if ($targetScalarType !== null) {
            if ($sourcePropertyValue === null) {
                return match ($targetScalarType) {
                    'int' => 0,
                    'float' => 0.0,
                    'string' => '',
                    'bool' => false,
                    'null' => null,
                };
            } elseif (is_scalar($sourcePropertyValue)) {
                return match ($targetScalarType) {
                    'int' => (int) $sourcePropertyValue,
                    'float' => (float) $sourcePropertyValue,
                    'string' => (string) $sourcePropertyValue,
                    'bool' => (bool) $sourcePropertyValue,
                    'null' => null,
                };
            }
        }

        // short circuit: if source is null & target accepts null, we set the
        // target to null

        if ($propertyMapping->targetCanAcceptNull() && $sourcePropertyValue === null) {
            return null;
        }

        // get the value of the target property if the target is an object and
        // target value reading is enabled

        if (
            is_object($target)
            && $context(MapperOptions::class)?->enableTargetValueReading
        ) {
            // if this is for a property mapping, not a constructor argument

            /** @var mixed */
            $targetPropertyValue = $this->readerWriter->readTargetProperty(
                $target,
                $propertyMapping,
                $context
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
                    $value[0]
                )
            ];
        }

        // guess source type, and get the compatible type from metadata, so
        // we can preserve generics information

        $guessedSourceType = TypeGuesser::guessTypeFromVariable($sourcePropertyValue);
        $sourceType = $propertyMapping->getCompatibleSourceType($guessedSourceType);

        // transform the value

        /** @var mixed */
        $targetPropertyValue = $this->getMainTransformer()->transform(
            source: $sourcePropertyValue,
            target: $targetPropertyValue,
            sourceType: $sourceType,
            targetTypes: $targetTypes,
            context: $context,
            path: $propertyMapping->getTargetProperty(),
        );

        return $targetPropertyValue;
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object(), true);
    }
}
