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
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\WriteMode;

/**
 * @internal
 */
class ReaderWriter
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

        if ($property === null) {
            return null;
        }

        if ($propertyMapping->getSourceReadVisibility() !== Visibility::Public) {
            throw new UnableToReadException(
                $source,
                $property,
                context: $context
            );
        }

        try {
            $accessorName = $propertyMapping->getSourceReadName();
            $mode = $propertyMapping->getSourceReadMode();

            if ($mode === ReadMode::Property) {
                return $source->{$accessorName};
            } elseif ($mode === ReadMode::Method) {
                /** @psalm-suppress MixedMethodCall */
                return $source->{$accessorName}();
            }
            return null;
        } catch (\Error $e) {
            if (\str_contains($e->getMessage(), 'must not be accessed before initialization')) {
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
            $propertyMapping->getTargetWriteMode() === WriteMode::AdderRemover
            && $propertyMapping->getTargetWriteVisibility() === Visibility::Public
        ) {
            return new AdderRemoverProxy(
                $target,
                $propertyMapping->getTargetWriteName(),
                null
            );
        }

        if ($propertyMapping->getTargetReadVisibility() !== Visibility::Public) {
            return null;
        }

        try {
            $accessorName = $propertyMapping->getTargetReadName();
            $readMode = $propertyMapping->getTargetReadMode();

            if ($readMode === ReadMode::Property) {
                return $target->{$accessorName};
            } elseif ($readMode === ReadMode::Method) {
                /** @psalm-suppress MixedMethodCall */
                return $target->{$accessorName}();
            }
            return null;
        } catch (\Error $e) {
            if (\str_contains($e->getMessage(), 'must not be accessed before initialization')) {
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
        if ($propertyMapping->getTargetWriteVisibility() !== Visibility::Public) {
            return;
        }

        try {
            $accessorName = $propertyMapping->getTargetWriteName();
            $writeMode = $propertyMapping->getTargetWriteMode();

            if ($writeMode === WriteMode::Property) {
                $target->{$accessorName} = $value;
            } elseif ($writeMode === WriteMode::Method) {
                /** @psalm-suppress MixedMethodCall */
                $target->{$accessorName}($value);
            } elseif ($writeMode === WriteMode::AdderRemover) {
                // noop
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
