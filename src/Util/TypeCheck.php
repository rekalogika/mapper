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

namespace Rekalogika\Mapper\Util;

use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Transformer\MixedType;
use Symfony\Component\PropertyInfo\Type;

final readonly class TypeCheck
{
    private function __construct() {}

    /**
     * Checks if the name is a valid class, interface, or enum
     *
     * @phpstan-assert class-string $class
     */
    public static function nameExists(string $class): bool
    {
        return class_exists($class)
            || interface_exists($class)
            || enum_exists($class);
    }

    public static function isInt(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_INT;
    }

    public static function isFloat(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_FLOAT;
    }

    public static function isString(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_STRING;
    }

    public static function isBool(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_BOOL;
    }

    public static function isArray(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_ARRAY;
    }

    public static function isObject(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        if ($type === null) {
            return false;
        }

        if ($type->getBuiltinType() !== Type::BUILTIN_TYPE_OBJECT) {
            return false;
        }

        $class = $type->getClassName();

        if ($class !== null) {
            return self::nameExists($class);
        }

        return true;
    }

    /**
     * @param class-string $classes
     */
    public static function isObjectOfType(null|Type|MixedType $type, string ...$classes): bool
    {
        if ($type === null || $type instanceof MixedType) {
            return false;
        }

        if (!self::isObject($type)) {
            return false;
        }

        $class = $type->getClassName();

        if ($class === null) {
            return false;
        }

        foreach ($classes as $classToMatch) {
            if (is_a($class, $classToMatch, true)) {
                return true;
            }
        }

        return false;
    }

    public static function isEnum(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        if ($type === null) {
            return false;
        }

        $class = $type->getClassName();

        return $type->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT
            && $class !== null
            && enum_exists($class);
    }

    public static function isBackedEnum(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        if ($type === null) {
            return false;
        }

        $class = $type->getClassName();

        return $type->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT
            && $class !== null
            && enum_exists($class)
            && is_a($class, \BackedEnum::class, true);
    }

    public static function isResource(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_RESOURCE;
    }

    public static function isNull(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_NULL;
    }

    public static function isScalar(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return self::isInt($type)
            || self::isFloat($type)
            || self::isString($type)
            || self::isBool($type);
    }

    public static function isIterable(null|Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_ITERABLE;
    }

    /**
     * Check for identity between Types, disregarding collection types
     */
    public static function isSomewhatIdentical(Type|MixedType $type1, Type|MixedType $type2): bool
    {
        if ($type1 instanceof MixedType && $type2 instanceof MixedType) {
            return true;
        } elseif ($type1 instanceof MixedType || $type2 instanceof MixedType) {
            return false;
        }

        return $type1->getBuiltinType() === $type2->getBuiltinType()
            && $type1->getClassName() === $type2->getClassName()
            && $type1->isNullable() === $type2->isNullable();
    }

    public static function isTypeInstanceOf(
        Type|MixedType $typeToCheck,
        Type|MixedType $type,
    ): bool {
        // instanceof mixed
        if ($type instanceof MixedType) {
            return true;
        }

        // mixed instanceof non mixed
        if ($typeToCheck instanceof MixedType) {
            return false;
        }

        if ($typeToCheck->isNullable() || $type->isNullable()) {
            throw new LogicException('Nullable types are not supported');
        }

        if ($typeToCheck->getBuiltinType() !== $type->getBuiltinType()) {
            return false;
        }

        // if not an object this is as far as we can go
        if ($typeToCheck->getBuiltinType() !== Type::BUILTIN_TYPE_OBJECT) {
            return true;
        }

        $typeToCheckClassName = $typeToCheck->getClassName();
        $className = $type->getClassName();

        // anyobject instanceof object
        if ($className === null) {
            return true;
        }

        // object instanceof specificobject
        if ($typeToCheckClassName === null) {
            return false;
        }

        if (!self::nameExists($className)) {
            throw new LogicException(\sprintf('Class "%s" does not exist', $className));
        }

        if (!self::nameExists($typeToCheckClassName)) {
            throw new LogicException(\sprintf('Class "%s" does not exist', $typeToCheckClassName));
        }

        return is_a($typeToCheckClassName, $className, true);
    }

    /**
     * @todo support generics
     */
    public static function isVariableInstanceOf(mixed $variable, Type|MixedType $type): bool
    {
        if ($type instanceof MixedType) {
            return true;
        }

        $builtinType = $type->getBuiltinType();

        switch ($builtinType) {
            case Type::BUILTIN_TYPE_INT:
                return \is_int($variable);
            case Type::BUILTIN_TYPE_FLOAT:
                return \is_float($variable);
            case Type::BUILTIN_TYPE_STRING:
                return \is_string($variable);
            case Type::BUILTIN_TYPE_BOOL:
                return \is_bool($variable);
            case Type::BUILTIN_TYPE_ARRAY:
                return \is_array($variable);
            case Type::BUILTIN_TYPE_RESOURCE:
                return \is_resource($variable);
            case Type::BUILTIN_TYPE_NULL:
                return \is_null($variable);
            case Type::BUILTIN_TYPE_ITERABLE:
                return is_iterable($variable);
            case Type::BUILTIN_TYPE_OBJECT:
                $class = $type->getClassName();

                if ($class === null) {
                    return true;
                }

                if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
                    return false;
                }

                return $variable instanceof $class;
            default:
                throw new LogicException(\sprintf('Unknown builtin type "%s"', $builtinType));
        }
    }

    /**
     * @param list<Type>|Type $type
     */
    public static function isRecursivelyImmutable(array|Type $type): bool
    {
        if (\is_array($type)) {
            foreach ($type as $t) {
                if (!self::isRecursivelyImmutable($t)) {
                    return false;
                }
            }

            return true;
        }

        $immutableTypes = [
            Type::BUILTIN_TYPE_INT,
            Type::BUILTIN_TYPE_FLOAT,
            Type::BUILTIN_TYPE_STRING,
            Type::BUILTIN_TYPE_BOOL,
            Type::BUILTIN_TYPE_RESOURCE,
            Type::BUILTIN_TYPE_NULL,
            Type::BUILTIN_TYPE_FALSE,
            Type::BUILTIN_TYPE_TRUE,
            Type::BUILTIN_TYPE_CALLABLE,
        ];

        $builtInType = $type->getBuiltinType();

        if (\in_array($builtInType, $immutableTypes, true)) {
            return true;
        }

        if ($builtInType === Type::BUILTIN_TYPE_OBJECT) {
            $class = $type->getClassName();

            if ($class === null) {
                return false;
            }

            if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
                return false;
            }

            return is_a($class, \DateTimeImmutable::class, true)
                || is_a($class, \DateTimeZone::class, true)
                || is_a($class, \DateInterval::class, true)
                || is_a($class, \DatePeriod::class, true);
        }

        return false;
    }

    /**
     * @param class-string $class
     */
    private static function isImmutable(string $class): bool
    {
        return is_a($class, \DateTimeImmutable::class, true)
            || is_a($class, \DateTimeZone::class, true)
            || is_a($class, \DateInterval::class, true)
            || is_a($class, \DatePeriod::class, true);
    }
}
