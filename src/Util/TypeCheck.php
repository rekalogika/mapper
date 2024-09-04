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
     * Checks if the name is a valid class, interface, or enum.
     *
     * @phpstan-assert class-string $class
     */
    public static function nameExists(string $class): bool
    {
        return class_exists($class)
            || interface_exists($class)
            || enum_exists($class);
    }

    public static function isInt(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return Type::BUILTIN_TYPE_INT === $type?->getBuiltinType();
    }

    public static function isFloat(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return Type::BUILTIN_TYPE_FLOAT === $type?->getBuiltinType();
    }

    public static function isString(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return Type::BUILTIN_TYPE_STRING === $type?->getBuiltinType();
    }

    public static function isBool(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return Type::BUILTIN_TYPE_BOOL === $type?->getBuiltinType();
    }

    public static function isArray(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return Type::BUILTIN_TYPE_ARRAY === $type?->getBuiltinType();
    }

    public static function isObject(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        if (null === $type) {
            return false;
        }

        if (Type::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType()) {
            return false;
        }

        $class = $type->getClassName();

        if (null !== $class) {
            return self::nameExists($class);
        }

        return true;
    }

    /**
     * @param class-string $classes
     */
    public static function isObjectOfType(null|MixedType|Type $type, string ...$classes): bool
    {
        if (null === $type || $type instanceof MixedType) {
            return false;
        }

        if (!self::isObject($type)) {
            return false;
        }

        $class = $type->getClassName();

        if (null === $class) {
            return false;
        }

        foreach ($classes as $classToMatch) {
            if (is_a($class, $classToMatch, true)) {
                return true;
            }
        }

        return false;
    }

    public static function isEnum(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        if (null === $type) {
            return false;
        }

        $class = $type->getClassName();

        return Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()
            && null !== $class
            && enum_exists($class);
    }

    public static function isBackedEnum(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        if (null === $type) {
            return false;
        }

        $class = $type->getClassName();

        return Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()
            && null !== $class
            && enum_exists($class)
            && is_a($class, \BackedEnum::class, true);
    }

    public static function isResource(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return Type::BUILTIN_TYPE_RESOURCE === $type?->getBuiltinType();
    }

    public static function isNull(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return Type::BUILTIN_TYPE_NULL === $type?->getBuiltinType();
    }

    public static function isScalar(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return self::isInt($type)
            || self::isFloat($type)
            || self::isString($type)
            || self::isBool($type);
    }

    public static function isIterable(null|MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return false;
        }

        return Type::BUILTIN_TYPE_ITERABLE === $type?->getBuiltinType();
    }

    /**
     * Check for identity between Types, disregarding collection types.
     */
    public static function isSomewhatIdentical(MixedType|Type $type1, MixedType|Type $type2): bool
    {
        if ($type1 instanceof MixedType && $type2 instanceof MixedType) {
            return true;
        }

        if ($type1 instanceof MixedType || $type2 instanceof MixedType) {
            return false;
        }

        return $type1->getBuiltinType() === $type2->getBuiltinType()
            && $type1->getClassName() === $type2->getClassName()
            && $type1->isNullable() === $type2->isNullable();
    }

    public static function isTypeInstanceOf(
        MixedType|Type $typeToCheck,
        MixedType|Type $type
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
        if (Type::BUILTIN_TYPE_OBJECT !== $typeToCheck->getBuiltinType()) {
            return true;
        }

        $typeToCheckClassName = $typeToCheck->getClassName();
        $className = $type->getClassName();

        // anyobject instanceof object
        if (null === $className) {
            return true;
        }

        // object instanceof specificobject
        if (null === $typeToCheckClassName) {
            return false;
        }

        if (!self::nameExists($className)) {
            throw new LogicException(sprintf('Class "%s" does not exist', $className));
        }

        if (!self::nameExists($typeToCheckClassName)) {
            throw new LogicException(sprintf('Class "%s" does not exist', $typeToCheckClassName));
        }

        return is_a($typeToCheckClassName, $className, true);
    }

    /**
     * @todo support generics
     */
    public static function isVariableInstanceOf(mixed $variable, MixedType|Type $type): bool
    {
        if ($type instanceof MixedType) {
            return true;
        }

        $builtinType = $type->getBuiltinType();

        switch ($builtinType) {
            case Type::BUILTIN_TYPE_INT:
                return is_int($variable);

            case Type::BUILTIN_TYPE_FLOAT:
                return is_float($variable);

            case Type::BUILTIN_TYPE_STRING:
                return is_string($variable);

            case Type::BUILTIN_TYPE_BOOL:
                return is_bool($variable);

            case Type::BUILTIN_TYPE_ARRAY:
                return is_array($variable);

            case Type::BUILTIN_TYPE_RESOURCE:
                return is_resource($variable);

            case Type::BUILTIN_TYPE_NULL:
                return is_null($variable);

            case Type::BUILTIN_TYPE_ITERABLE:
                return is_iterable($variable);

            case Type::BUILTIN_TYPE_OBJECT:
                $class = $type->getClassName();

                if (null === $class) {
                    return true;
                }

                if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
                    return false;
                }

                return $variable instanceof $class;

            default:
                throw new LogicException(sprintf('Unknown builtin type "%s"', $builtinType));
        }
    }
}
