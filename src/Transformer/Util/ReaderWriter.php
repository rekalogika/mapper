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
use Rekalogika\Mapper\Transformer\Exception\UnableToReadException;
use Rekalogika\Mapper\Transformer\Exception\UnableToWriteException;
use Rekalogika\Mapper\Transformer\Exception\UninitializedSourcePropertyException;
use Rekalogika\Mapper\Transformer\Model\AdderRemoverProxy;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;

/**
 * @internal
 */
final readonly class ReaderWriter
{
    /**
     * @throws UninitializedSourcePropertyException
     * @throws UnableToReadException
     */
    public function readSourceProperty(
        object $source,
        PropertyMapping $propertyMapping,
        Context $context
    ): mixed {
        $property = $propertyMapping->getSourceProperty();

        if (null === $property) {
            return null;
        }

        if (Visibility::Public !== $propertyMapping->getSourceReadVisibility()) {
            throw new UnableToReadException(
                $source,
                $property,
                context: $context
            );
        }

        try {
            $accessorName = $propertyMapping->getSourceReadName();
            $mode = $propertyMapping->getSourceReadMode();

            if (ReadMode::Property === $mode) {
                return $source->{$accessorName};
            }

            if (ReadMode::Method === $mode) {
                /** @psalm-suppress MixedMethodCall */
                return $source->{$accessorName}();
            }

            if (ReadMode::DynamicProperty === $mode) {
                return $source->{$accessorName} ?? null;
            }

            return null;
        } catch (\Error $e) {
            $message = $e->getMessage();

            if (
                \str_contains($message, 'must not be accessed before initialization')
                || \str_contains($message, 'Cannot access uninitialized non-nullable property')
            ) {
                throw new UninitializedSourcePropertyException($property);
            }

            throw new UnableToReadException(
                $source,
                $property,
                context: $context,
                previous: $e
            );
        }
    }

    /**
     * @throws UnableToReadException
     */
    public function readTargetProperty(
        object $target,
        PropertyMapping $propertyMapping,
        Context $context
    ): mixed {
        if (
            WriteMode::AdderRemover === $propertyMapping->getTargetSetterWriteMode()
            && Visibility::Public === $propertyMapping->getTargetSetterWriteVisibility()
        ) {
            if (Visibility::Public === $propertyMapping->getTargetRemoverWriteVisibility()
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

        if (Visibility::Public !== $propertyMapping->getTargetReadVisibility()) {
            return null;
        }

        try {
            $accessorName = $propertyMapping->getTargetReadName();
            $readMode = $propertyMapping->getTargetReadMode();

            if (ReadMode::Property === $readMode) {
                return $target->{$accessorName};
            }

            if (ReadMode::Method === $readMode) {
                /** @psalm-suppress MixedMethodCall */
                return $target->{$accessorName}();
            }

            if (ReadMode::DynamicProperty === $readMode) {
                return $target->{$accessorName} ?? null;
            }

            return null;
        } catch (\Error $e) {
            $message = $e->getMessage();

            if (
                \str_contains($message, 'must not be accessed before initialization')
                || \str_contains($message, 'Cannot access uninitialized non-nullable property')
            ) {
                return null;
            }

            throw new UnableToReadException(
                $target,
                $propertyMapping->getTargetProperty(),
                context: $context,
                previous: $e
            );
        }
    }

    /**
     * @throws UnableToWriteException
     */
    public function writeTargetProperty(
        object $target,
        PropertyMapping $propertyMapping,
        mixed $value,
        Context $context
    ): void {
        if (Visibility::Public !== $propertyMapping->getTargetSetterWriteVisibility()) {
            return;
        }

        try {
            $accessorName = $propertyMapping->getTargetSetterWriteName();
            $writeMode = $propertyMapping->getTargetSetterWriteMode();

            if (WriteMode::Property === $writeMode) {
                $target->{$accessorName} = $value;
            } elseif (WriteMode::Method === $writeMode) {
                /** @psalm-suppress MixedMethodCall */
                $target->{$accessorName}($value);
            } elseif (WriteMode::AdderRemover === $writeMode) {
                // noop
            } elseif (WriteMode::DynamicProperty === $writeMode) {
                $target->{$accessorName} = $value;
            }
        } catch (\Throwable $e) {
            throw new UnableToWriteException(
                $target,
                $propertyMapping->getTargetProperty(),
                context: $context,
                previous: $e
            );
        }
    }
}
