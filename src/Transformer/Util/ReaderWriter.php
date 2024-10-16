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

namespace Rekalogika\Mapper\Transformer\Util;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Transformer\Exception\NewInstanceReturnedButCannotBeSetOnTargetException;
use Rekalogika\Mapper\Transformer\Exception\UnableToReadException;
use Rekalogika\Mapper\Transformer\Exception\UnableToWriteException;
use Rekalogika\Mapper\Transformer\Exception\UninitializedSourcePropertyException;
use Rekalogika\Mapper\Transformer\Model\AdderRemoverProxy;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMappingMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal
 * @todo inject logger
 */
final readonly class ReaderWriter
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
    ) {}

    /**
     * @throws UninitializedSourcePropertyException
     * @throws UnableToReadException
     */
    public function readSourceProperty(
        object $source,
        PropertyMappingMetadata $propertyMapping,
        Context $context,
    ): mixed {
        $property = $propertyMapping->getSourceProperty();

        if ($property === null) {
            return null;
        }

        if ($propertyMapping->getSourceReadVisibility() !== Visibility::Public) {
            throw new UnableToReadException(
                $source,
                $property,
                context: $context,
            );
        }

        try {
            $accessorName = $propertyMapping->getSourceReadName();
            $mode = $propertyMapping->getSourceReadMode();

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
    public function readTargetProperty(
        object $target,
        PropertyMappingMetadata $propertyMapping,
        Context $context,
    ): mixed {
        if (
            $propertyMapping->getTargetSetterWriteMode() === WriteMode::AdderRemover
            && $propertyMapping->getTargetSetterWriteVisibility() === Visibility::Public
        ) {
            if (
                $propertyMapping->getTargetRemoverWriteVisibility() === Visibility::Public
            ) {
                $removerMethodName = $propertyMapping->getTargetRemoverWriteName();
            } else {
                $removerMethodName = null;
            }

            return new AdderRemoverProxy(
                hostObject: $target,
                getterMethodName: $propertyMapping->getTargetReadName(),
                adderMethodName: $propertyMapping->getTargetSetterWriteName(),
                removerMethodName: $removerMethodName,
            );
        }

        if ($propertyMapping->getTargetReadVisibility() !== Visibility::Public) {
            return null;
        }

        try {
            $accessorName = $propertyMapping->getTargetReadName();
            $readMode = $propertyMapping->getTargetReadMode();

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
                $propertyMapping->getTargetProperty(),
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
        PropertyMappingMetadata $propertyMapping,
        mixed $value,
        Context $context,
        bool $silentOnError,
    ): object {
        $accessorName = $propertyMapping->getTargetSetterWriteName();
        $writeMode = $propertyMapping->getTargetSetterWriteMode();
        $visibility = $propertyMapping->getTargetSetterWriteVisibility();

        if (
            $visibility !== Visibility::Public
            || $writeMode === WriteMode::None
        ) {
            if ($silentOnError) {
                return $target;
            }

            throw new NewInstanceReturnedButCannotBeSetOnTargetException(
                $target,
                $propertyMapping->getTargetProperty(),
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
                if ($propertyMapping->isTargetSetterVariadic()) {
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
                $propertyMapping->getTargetProperty(),
                context: $context,
                previous: $e,
            );
        }

        return $target;
    }
}
