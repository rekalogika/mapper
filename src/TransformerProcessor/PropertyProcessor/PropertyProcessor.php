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

namespace Rekalogika\Mapper\TransformerProcessor\PropertyProcessor;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\MapperOptions;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodRunner;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\Exception\NewInstanceReturnedButCannotBeSetOnTargetException;
use Rekalogika\Mapper\Transformer\Exception\UnableToReadException;
use Rekalogika\Mapper\Transformer\Exception\UnableToWriteException;
use Rekalogika\Mapper\Transformer\Exception\UninitializedSourcePropertyException;
use Rekalogika\Mapper\Transformer\Exception\UnsupportedPropertyMappingException;
use Rekalogika\Mapper\Transformer\Model\AdderRemoverProxy;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorInterface;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class PropertyProcessor implements PropertyProcessorInterface
{
    public function __construct(
        private PropertyMapping $metadata,
        private PropertyAccessorInterface $propertyAccessor,
        private MainTransformerInterface $mainTransformer,
        private SubMapperFactoryInterface $subMapperFactory,
        private ContainerInterface $propertyMapperLocator,
    ) {}

    /**
     * @return object The target object after writing the property, can be of a
     * different instance but should be of the same class
     */
    public function readSourcePropertyAndWriteTargetProperty(
        object $source,
        object $target,
        Context $context,
    ): object {
        if (
            $this->metadata->getTargetReadMode() === ReadMode::None
            && $this->metadata->getTargetSetterWriteMode() === WriteMode::None
        ) {
            return $target;
        }

        try {
            /** @var mixed $targetPropertyValue */
            [$targetPropertyValue, $isChanged] = $this->transformValue(
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
            || $this->metadata->getTargetSetterWriteMode() === WriteMode::DynamicProperty
        ) {
            if ($targetPropertyValue instanceof AdderRemoverProxy) {
                $target = $targetPropertyValue->getHostObject();
            }

            return $this->writeTargetProperty(
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
        object $source,
        ?object $target,
        bool $mandatory,
        Context $context,
    ): mixed {
        // if a custom property mapper is set, then use it

        if ($this->metadata->hasPropertyMapper()) {
            /** @psalm-suppress MixedReturnStatement */
            return $this->transformValueUsingPropertyMapper(
                source: $source,
                target: $target,
                context: $context,
            );
        }

        // if source property name is null, continue. there is nothing to
        // transform

        $sourceProperty = $this->metadata->getSourceProperty();

        if ($sourceProperty === null) {
            throw new UnsupportedPropertyMappingException();
        }

        // get the value of the source property

        try {
            /** @var mixed */
            $sourcePropertyValue = $this->readSourceProperty(
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

            if ($this->metadata->targetCanAcceptNull() && $sourcePropertyValue === null) {
                return [null, true];
            }

            // if the the source is null or scalar, and the target is a scalar

            $targetScalarType = $this->metadata->getTargetScalarType();

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
                target: $target,
                context: $context,
            );
        } else {
            // if this is for a constructor argument, we don't have an existing
            // value

            $targetPropertyValue = null;
        }

        // if we get an AdderRemoverProxy, change the target type

        $targetTypes = $this->metadata->getTargetTypes();

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
        $sourceType = $this->metadata->getCompatibleSourceType($guessedSourceType)
            ?? $guessedSourceType;

        // add attributes to context

        $sourceAttributes = $this->metadata->getSourceAttributes();
        $context = $context->with($sourceAttributes);

        $targetAttributes = $this->metadata->getTargetAttributes();
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
            path: $this->metadata->getTargetProperty(),
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
        object $source,
        ?object $target,
        Context $context,
    ): array {
        $serviceMethodSpecification = $this->metadata->getPropertyMapper();

        if ($serviceMethodSpecification === null) {
            throw new UnexpectedValueException('PropertyMapper is null', context: $context);
        }

        if ($target === null) {
            $targetPropertyValue = null;
        } else {
            /** @var mixed */
            $targetPropertyValue = $this->readTargetProperty(
                $target,
                $context,
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
        object $source,
        Context $context,
    ): mixed {
        $property = $this->metadata->getSourceProperty();

        if ($property === null) {
            return null;
        }

        if ($this->metadata->getSourceReadVisibility() !== Visibility::Public) {
            throw new UnableToReadException(
                $source,
                $property,
                context: $context,
            );
        }

        try {
            $accessorName = $this->metadata->getSourceReadName();
            $mode = $this->metadata->getSourceReadMode();

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
                $source,
                $property,
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
        object $target,
        Context $context,
    ): mixed {
        if (
            $this->metadata->getTargetSetterWriteMode() === WriteMode::AdderRemover
            && $this->metadata->getTargetSetterWriteVisibility() === Visibility::Public
        ) {
            if (
                $this->metadata->getTargetRemoverWriteVisibility() === Visibility::Public
            ) {
                $removerMethodName = $this->metadata->getTargetRemoverWriteName();
            } else {
                $removerMethodName = null;
            }

            return new AdderRemoverProxy(
                hostObject: $target,
                getterMethodName: $this->metadata->getTargetReadName(),
                adderMethodName: $this->metadata->getTargetSetterWriteName(),
                removerMethodName: $removerMethodName,
            );
        }

        if ($this->metadata->getTargetReadVisibility() !== Visibility::Public) {
            return null;
        }

        try {
            $accessorName = $this->metadata->getTargetReadName();
            $readMode = $this->metadata->getTargetReadMode();

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
                $target,
                $this->metadata->getTargetProperty(),
                context: $context,
                previous: $e,
            );
        }
    }

    /**
     * @throws UnableToWriteException
     */
    public function writeTargetProperty(
        object $target,
        mixed $value,
        Context $context,
        bool $silentOnError,
    ): object {
        $accessorName = $this->metadata->getTargetSetterWriteName();
        $writeMode = $this->metadata->getTargetSetterWriteMode();
        $visibility = $this->metadata->getTargetSetterWriteVisibility();

        if (
            $visibility !== Visibility::Public
            || $writeMode === WriteMode::None
        ) {
            if ($silentOnError) {
                return $target;
            }

            throw new NewInstanceReturnedButCannotBeSetOnTargetException(
                $target,
                $this->metadata->getTargetProperty(),
                context: $context,
            );
        }

        if ($accessorName === null) {
            throw new UnexpectedValueException('AccessorName is null', context: $context);
        }

        try {
            if ($writeMode === WriteMode::Property) {
                $target->{$accessorName} = $value;
            } elseif ($writeMode === WriteMode::Method) {
                if ($this->metadata->isTargetSetterVariadic()) {
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
                $target,
                $this->metadata->getTargetProperty(),
                context: $context,
                previous: $e,
            );
        }

        return $target;
    }
}
