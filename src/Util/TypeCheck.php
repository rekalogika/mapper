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
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

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

    private static function isBuiltinIdentifiedBy(?Type $type, TypeIdentifier $identifier): bool
    {
        return $type instanceof BuiltinType
            && $type->getTypeIdentifier() === $identifier;
    }

    public static function isInt(?Type $type): bool
    {
        return self::isBuiltinIdentifiedBy($type, TypeIdentifier::INT);
    }

    public static function isFloat(?Type $type): bool
    {
        return self::isBuiltinIdentifiedBy($type, TypeIdentifier::FLOAT);
    }

    public static function isString(?Type $type): bool
    {
        return self::isBuiltinIdentifiedBy($type, TypeIdentifier::STRING);
    }

    public static function isBool(?Type $type): bool
    {
        return self::isBuiltinIdentifiedBy($type, TypeIdentifier::BOOL);
    }

    public static function isIntOrString(?Type $type): bool
    {
        return self::isInt($type)
            || self::isString($type);
    }

    public static function isArray(?Type $type): bool
    {
        if ($type === null) {
            return false;
        }

        // Type::array() returns CollectionType wrapping BuiltinType<ARRAY>
        $unwrapped = self::unwrapToInner($type);

        return $unwrapped instanceof BuiltinType
            && $unwrapped->getTypeIdentifier() === TypeIdentifier::ARRAY;
    }

    public static function isIterable(?Type $type): bool
    {
        if ($type === null) {
            return false;
        }

        $unwrapped = self::unwrapToInner($type);

        return $unwrapped instanceof BuiltinType
            && $unwrapped->getTypeIdentifier() === TypeIdentifier::ITERABLE;
    }

    public static function isObject(?Type $type): bool
    {
        if ($type === null) {
            return false;
        }

        $unwrapped = self::unwrapToInner($type);

        if ($unwrapped instanceof ObjectType) {
            return self::nameExists($unwrapped->getClassName());
        }

        return $unwrapped instanceof BuiltinType
            && $unwrapped->getTypeIdentifier() === TypeIdentifier::OBJECT;
    }

    /**
     * @param class-string $classes
     */
    public static function isObjectOfType(?Type $type, string ...$classes): bool
    {
        if ($type === null) {
            return false;
        }

        $unwrapped = self::unwrapToInner($type);

        if (!$unwrapped instanceof ObjectType) {
            return false;
        }

        $class = $unwrapped->getClassName();

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

        $unwrapped = self::unwrapToInner($type);

        if ($unwrapped instanceof EnumType) {
            return true;
        }

        return $unwrapped instanceof ObjectType
            && enum_exists($unwrapped->getClassName());
    }

    public static function isBackedEnum(?Type $type): bool
    {
        if ($type === null) {
            return false;
        }

        $unwrapped = self::unwrapToInner($type);

        if ($unwrapped instanceof BackedEnumType) {
            return true;
        }

        if (!$unwrapped instanceof ObjectType) {
            return false;
        }

        $class = $unwrapped->getClassName();

        return enum_exists($class)
            && is_a($class, \BackedEnum::class, true);
    }

    public static function isResource(?Type $type): bool
    {
        return self::isBuiltinIdentifiedBy($type, TypeIdentifier::RESOURCE);
    }

    public static function isNull(?Type $type): bool
    {
        return self::isBuiltinIdentifiedBy($type, TypeIdentifier::NULL);
    }

    public static function isMixed(?Type $type): bool
    {
        return self::isBuiltinIdentifiedBy($type, TypeIdentifier::MIXED);
    }

    public static function isScalar(?Type $type): bool
    {
        if ($type === null) {
            return false;
        }

        return self::isInt($type)
            || self::isFloat($type)
            || self::isString($type)
            || self::isBool($type);
    }

    /**
     * Check for identity between Types, disregarding collection types
     */
    public static function isSomewhatIdentical(Type $type1, Type $type2): bool
    {
        if (self::isMixed($type1) && self::isMixed($type2)) {
            return true;
        }

        if (self::isMixed($type1) || self::isMixed($type2)) {
            return false;
        }

        if ($type1->isNullable() !== $type2->isNullable()) {
            return false;
        }

        [$id1, $class1] = self::getCoreIdentity($type1);
        [$id2, $class2] = self::getCoreIdentity($type2);

        return $id1 === $id2 && $class1 === $class2;
    }

    public static function isTypeInstanceOf(
        Type $typeToCheck,
        Type $type,
    ): bool {
        // instanceof mixed
        if (self::isMixed($type)) {
            return true;
        }

        // mixed instanceof non mixed
        if (self::isMixed($typeToCheck)) {
            return false;
        }

        if ($typeToCheck->isNullable() || $type->isNullable()) {
            throw new LogicException('Nullable types are not supported');
        }

        [$idCheck, $classCheck] = self::getCoreIdentity($typeToCheck);
        [$idType, $classType] = self::getCoreIdentity($type);

        if ($idCheck !== $idType) {
            return false;
        }

        // if not an object this is as far as we can go
        if ($idType !== TypeIdentifier::OBJECT) {
            return true;
        }

        // anyobject instanceof object
        if ($classType === null) {
            return true;
        }

        // object instanceof specificobject
        if ($classCheck === null) {
            return false;
        }

        if (!self::nameExists($classType)) {
            throw new LogicException(\sprintf('Class "%s" does not exist', $classType));
        }

        if (!self::nameExists($classCheck)) {
            throw new LogicException(\sprintf('Class "%s" does not exist', $classCheck));
        }

        return is_a($classCheck, $classType, true);
    }

    public static function isVariableInstanceOf(mixed $variable, Type $type): bool
    {
        if (self::isMixed($type)) {
            return true;
        }

        $unwrapped = self::unwrapToInner($type);

        if ($unwrapped instanceof ObjectType) {
            $class = $unwrapped->getClassName();

            if (!self::nameExists($class)) {
                return false;
            }

            return $variable instanceof $class;
        }

        if (!$unwrapped instanceof BuiltinType) {
            return $type->accepts($variable);
        }

        return match ($unwrapped->getTypeIdentifier()) {
            TypeIdentifier::INT => \is_int($variable),
            TypeIdentifier::FLOAT => \is_float($variable),
            TypeIdentifier::STRING => \is_string($variable),
            TypeIdentifier::BOOL, TypeIdentifier::TRUE, TypeIdentifier::FALSE => \is_bool($variable),
            TypeIdentifier::ARRAY => \is_array($variable),
            TypeIdentifier::RESOURCE => \is_resource($variable),
            TypeIdentifier::NULL => $variable === null,
            TypeIdentifier::ITERABLE => is_iterable($variable),
            TypeIdentifier::OBJECT => \is_object($variable),
            TypeIdentifier::CALLABLE => \is_callable($variable),
            TypeIdentifier::MIXED => true,
            default => throw new LogicException(\sprintf('Unknown builtin type "%s"', $unwrapped->getTypeIdentifier()->value)),
        };
    }

    /**
     * Strips wrapping types (NullableType, CollectionType, GenericType) and
     * returns the innermost type. Useful when collection metadata is irrelevant
     * for the check.
     */
    private static function unwrapToInner(Type $type): Type
    {
        while ($type instanceof WrappingTypeInterface) {
            $type = $type->getWrappedType();
        }

        return $type;
    }

    /**
     * Returns the identity of a type as a (TypeIdentifier, ?class-string) pair,
     * disregarding wrapping (nullability, collection metadata, generics).
     *
     * @return array{0: TypeIdentifier, 1: ?class-string}
     */
    private static function getCoreIdentity(Type $type): array
    {
        // Strip nullability first.
        if ($type instanceof NullableType) {
            $type = $type->getWrappedType();
        }

        $unwrapped = self::unwrapToInner($type);

        if ($unwrapped instanceof BuiltinType) {
            return [$unwrapped->getTypeIdentifier(), null];
        }

        if ($unwrapped instanceof ObjectType) {
            /** @var class-string $className */
            $className = $unwrapped->getClassName();

            return [TypeIdentifier::OBJECT, $className];
        }

        throw new LogicException(\sprintf(
            'Cannot determine core identity of type "%s"',
            $type::class,
        ));
    }
}
