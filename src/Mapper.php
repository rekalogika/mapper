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

namespace Rekalogika\Mapper;

use Rekalogika\Mapper\Contracts\MainTransformerInterface;
use Rekalogika\Mapper\Contracts\MixedType;
use Rekalogika\Mapper\Exception\MapperReturnsUnexpectedValueException;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class Mapper implements MapperInterface
{
    /**
     * Informs the key and value type of the member of the target collection.
     */
    public const TARGET_KEY_TYPE = 'target_key_type';
    public const TARGET_VALUE_TYPE = 'target_value_type';

    public function __construct(
        private MainTransformerInterface $transformer,
    ) {
    }

    public function map(mixed $source, mixed $target, array $context = []): mixed
    {
        $originalTarget = $target;

        if (
            is_string($target)
            && (
                class_exists($target)
                || interface_exists($target)
                || enum_exists($target)
            )
        ) {
            /** @var class-string $target */
            $targetClass = $target;
            $targetType = TypeFactory::objectOfClass($targetClass);
            $target = null;
        } elseif (is_object($target)) {
            /** @var object $target */
            $targetClass = $target::class;
            $targetType = TypeFactory::objectOfClass($targetClass);
        } else {
            $targetClass = null;
            $targetType = TypeFactory::fromBuiltIn($target);
            $target = null;
        }

        /** @var ?string */
        $contextTargetKeyType = $context[self::TARGET_KEY_TYPE] ?? null;
        /** @var ?string */
        $contextTargetValueType = $context[self::TARGET_VALUE_TYPE] ?? null;
        unset($context[self::TARGET_KEY_TYPE]);
        unset($context[self::TARGET_VALUE_TYPE]);

        $targetKeyType = null;
        $targetValueType = null;

        if ($contextTargetKeyType) {
            $targetKeyType = TypeFactory::fromString($contextTargetKeyType);
            if ($targetKeyType instanceof MixedType) {
                $targetKeyType = null;
            }
        }

        if ($contextTargetValueType) {
            $targetValueType = TypeFactory::fromString($contextTargetValueType);
            if ($targetValueType instanceof MixedType) {
                $targetValueType = null;
            }
        }

        if ($targetKeyType !== null || $targetValueType !== null) {
            $targetType = new Type(
                builtinType: $targetType->getBuiltinType(),
                nullable: $targetType->isNullable(),
                class: $targetType->getClassName(),
                collection: true,
                collectionKeyType: $targetKeyType,
                collectionValueType: $targetValueType,
            );
        }

        /** @var mixed */
        $target = $this->transformer->transform(
            source: $source,
            target: $target,
            targetType: $targetType,
            context: $context
        );

        if (is_object($target) && is_string($targetClass)) {
            if (!is_a($target, $targetClass)) {
                throw new UnexpectedValueException(sprintf('The transformer did not return the variable of expected class, expecting "%s", returned "%s".', $targetClass, get_debug_type($target)));
            }
            return $target;
        }

        if ($originalTarget === 'string' && is_string($target)) {
            return $target;
        }

        if ($originalTarget === 'int' && is_int($target)) {
            return $target;
        }

        if ($originalTarget === 'float' && is_float($target)) {
            return $target;
        }

        if ($originalTarget === 'bool' && is_bool($target)) {
            return $target;
        }

        if ($originalTarget === 'array' && is_array($target)) {
            return $target;
        }

        throw new MapperReturnsUnexpectedValueException($targetType, $target);
    }
}
