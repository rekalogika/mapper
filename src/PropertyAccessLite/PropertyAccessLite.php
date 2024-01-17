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

namespace Rekalogika\Mapper\PropertyAccessLite;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

final class PropertyAccessLite implements PropertyAccessorInterface
{
    /**
     * @param object|array<array-key,mixed> $objectOrArray
     */
    public function getValue(
        object|array $objectOrArray,
        string|PropertyPathInterface $propertyPath,
    ): mixed {
        assert(is_string($propertyPath));
        assert(\is_object($objectOrArray));

        $getter = 'get' . ucfirst($propertyPath);

        try {
            /** @psalm-suppress MixedMethodCall */
            return $objectOrArray->{$getter}();
        } catch (\Throwable $e) {
            if (!\str_starts_with($e->getMessage(), 'Call to undefined method')) {
                if (\str_starts_with($e->getMessage(), 'Typed property')) {
                    throw new UninitializedPropertyException(\sprintf(
                        'Property "%s" is not initialized in object "%s"',
                        $propertyPath,
                        \get_class($objectOrArray),
                    ), 0, $e);
                }

                throw $e;
            }
        }

        try {
            return $objectOrArray->{$propertyPath};
            // @phpstan-ignore-next-line
        } catch (\Throwable $e) {
            if (\str_starts_with($e->getMessage(), 'Typed property')) {
                throw new UninitializedPropertyException(\sprintf(
                    'Property "%s" is not initialized in object "%s"',
                    $propertyPath,
                    \get_class($objectOrArray),
                ), 0, $e);
            } elseif (\str_starts_with($e->getMessage(), 'Cannot access private property')) {
                throw new NoSuchPropertyException(\sprintf(
                    'Property "%s" is not public in object "%s"',
                    $propertyPath,
                    \get_class($objectOrArray),
                ), 0, $e);
            } elseif (\str_starts_with($e->getMessage(), 'Undefined property')) {
                throw new NoSuchPropertyException(\sprintf(
                    'Property "%s" is not defined in object "%s"',
                    $propertyPath,
                    \get_class($objectOrArray),
                ), 0, $e);
            }

            throw $e;
        }
    }

    /**
     * @param object|array<array-key,mixed> $objectOrArray
     */
    public function setValue(
        object|array &$objectOrArray,
        string|PropertyPathInterface $propertyPath,
        mixed $value
    ): void {
        assert(is_string($propertyPath));
        assert(\is_object($objectOrArray));

        $setter = 'set' . ucfirst($propertyPath);

        try {
            /** @psalm-suppress MixedMethodCall */
            $objectOrArray->{$setter}($value);
            return;
        } catch (\Throwable $e) {
            if (!\str_starts_with($e->getMessage(), 'Call to undefined method')) {
                throw $e;
            }
        }

        try {
            if (!\property_exists($objectOrArray, $propertyPath)) {
                throw new NoSuchPropertyException(\sprintf(
                    'Property "%s" is not defined in object "%s"',
                    $propertyPath,
                    \get_class($objectOrArray),
                ));
            }
            $objectOrArray->{$propertyPath} = $value;
        } catch (NoSuchPropertyException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if (\str_starts_with($e->getMessage(), 'Cannot access private property')) {
                throw new NoSuchPropertyException(\sprintf(
                    'Property "%s" is not public in object "%s"',
                    $propertyPath,
                    \get_class($objectOrArray),
                ), 0, $e);
            }

            throw $e;
        }
    }

    /**
     * @param object|array<array-key,mixed> $objectOrArray
     */
    public function isWritable(
        object|array $objectOrArray,
        string|PropertyPathInterface $propertyPath
    ): bool {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * @param object|array<array-key,mixed> $objectOrArray
     */
    public function isReadable(
        object|array $objectOrArray,
        string|PropertyPathInterface $propertyPath
    ): bool {
        throw new \RuntimeException('Not implemented');
    }
}
