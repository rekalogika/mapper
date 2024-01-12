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

use Rekalogika\Mapper\Contracts\MixedType;
use Rekalogika\Mapper\Exception\LogicException;
use Symfony\Component\PropertyInfo\Type;

class TypeCheck
{
    private function __construct()
    {
    }

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

    public static function isInt(?Type $type): bool
    {
        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_INT;
    }

    public static function isFloat(?Type $type): bool
    {
        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_FLOAT;
    }

    public static function isString(?Type $type): bool
    {
        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_STRING;
    }

    public static function isBool(?Type $type): bool
    {
        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_BOOL;
    }

    public static function isArray(?Type $type): bool
    {
        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_ARRAY;
    }

    public static function isObject(?Type $type): bool
    {
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
    public static function isObjectOfType(?Type $type, string ...$classes): bool
    {
        if ($type === null) {
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

    public static function isEnum(?Type $type): bool
    {
        if ($type === null) {
            return false;
        }
        return $type->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT
            && $type->getClassName() !== null
            && enum_exists($type->getClassName());
    }

    public static function isResource(?Type $type): bool
    {
        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_RESOURCE;
    }

    public static function isNull(?Type $type): bool
    {
        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_NULL;
    }

    public static function isScalar(?Type $type): bool
    {
        return self::isInt($type)
            || self::isFloat($type)
            || self::isString($type)
            || self::isBool($type);
    }

    public static function isIterable(?Type $type): bool
    {
        return $type?->getBuiltinType() === Type::BUILTIN_TYPE_ITERABLE;
    }

    /**
     * Check for identity between Types, disregarding collection types
     *
     * @param Type $type1
     * @param Type $type2
     * @return boolean
     */
    public static function isSomewhatIdentical(Type $type1, Type $type2): bool
    {
        return $type1->getBuiltinType() === $type2->getBuiltinType()
            && $type1->getClassName() === $type2->getClassName()
            && $type1->isNullable() === $type2->isNullable();
    }

    /**
     * @param Type|MixedType $typeToCheck
     * @param Type|MixedType $type
     */
    public static function isTypeInstanceOf(
        Type|MixedType $typeToCheck,
        Type|MixedType $type
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
    public static function isVariableInstanceOf(mixed $variable, Type $type): bool
    {
        if (self::isObject($type)) {
            $class = $type->getClassName();

            if ($class !== null) {
                return self::nameExists($class)
                    && $variable instanceof $class;
            }

            return true;
        }

        if (self::isInt($type)) {
            return is_int($variable);
        }

        if (self::isFloat($type)) {
            return is_float($variable);
        }

        if (self::isString($type)) {
            return is_string($variable);
        }

        if (self::isBool($type)) {
            return is_bool($variable);
        }

        if (self::isArray($type)) {
            return is_array($variable);
        }

        if (self::isResource($type)) {
            return is_resource($variable);
        }

        if (self::isNull($type)) {
            return is_null($variable);
        }

        if (self::isIterable($type)) {
            return is_iterable($variable);
        }

        return false;
    }
}
