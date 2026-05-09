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

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Exception\InvalidClassException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Convenience factory to instantiate Type objects.
 */
final class TypeFactory
{
    private function __construct() {}

    /**
     * Convert a string type notation to a Type object. Currently does not do
     * anything fancy. Just supports the built-in types and class names.
     */
    public static function fromString(string $string): Type
    {
        $result = match ($string) {
            'mixed' => self::mixed(),
            'null' => self::null(),
            'string' => self::string(),
            'int' => self::int(),
            'float' => self::float(),
            'bool' => self::bool(),
            'resource' => self::resource(),
            'true' => self::true(),
            'false' => self::false(),
            'callable' => self::callable(),
            'array' => self::array(),
            'object' => self::object(),
            'iterable' => self::iterable(),
            default => null,
        };

        if ($result !== null) {
            return $result;
        }

        // remove possible nullable prefix
        $string = preg_replace('/^\?/', '', $string)
            ?? throw new InvalidArgumentException(\sprintf('"%s" does not appear to be a valid type.', $string));

        // remove generics notation
        $string = preg_replace('/<.*>/', '', $string)
            ?? throw new InvalidArgumentException(\sprintf('"%s" does not appear to be a valid type.', $string));

        if (!TypeCheck::nameExists($string)) {
            throw new InvalidArgumentException(\sprintf('"%s" does not appear to be a valid type.', $string));
        }

        return self::objectOfClass($string);
    }

    public static function mixed(): Type
    {
        return Type::mixed();
    }

    public static function null(): Type
    {
        return Type::null();
    }

    public static function string(): Type
    {
        return Type::string();
    }

    public static function int(): Type
    {
        return Type::int();
    }

    public static function float(): Type
    {
        return Type::float();
    }

    public static function bool(): Type
    {
        return Type::bool();
    }

    public static function resource(): Type
    {
        return Type::resource();
    }

    public static function true(): Type
    {
        return Type::true();
    }

    public static function false(): Type
    {
        return Type::false();
    }

    public static function callable(): Type
    {
        return Type::callable();
    }

    public static function array(): Type
    {
        return Type::array();
    }

    public static function iterable(): Type
    {
        return Type::iterable();
    }

    public static function arrayWithKeyValue(
        ?Type $keyType,
        ?Type $valueType,
    ): Type {
        // Arrays must have int|string keys; preserve the legacy "no info"
        // semantics by defaulting a missing key to the array-key union and a
        // missing value to mixed. Always produces a generic CollectionType
        // (mirrors legacy `collection: true` flag).
        return Type::array(
            $valueType ?? Type::mixed(),
            $keyType ?? Type::arrayKey(),
        );
    }

    public static function arrayOfObject(string $class): Type
    {
        if (!TypeCheck::nameExists($class)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid class.', $class));
        }

        return Type::array(self::objectOfClass($class));
    }

    public static function object(): Type
    {
        return Type::object();
    }

    public static function objectOfClass(string $class): Type
    {
        if (!TypeCheck::nameExists($class)) {
            throw new InvalidClassException($class);
        }

        return Type::object($class);
    }

    public static function objectWithKeyValue(
        string $class,
        ?Type $keyType,
        ?Type $valueType,
    ): Type {
        if (!TypeCheck::nameExists($class)) {
            throw new InvalidClassException($class);
        }

        // Always produces a generic CollectionType (mirrors legacy
        // `collection: true` flag). A missing key or value type means "mixed".
        return Type::collection(
            new ObjectType($class),
            $valueType ?? Type::mixed(),
            $keyType ?? Type::mixed(),
        );
    }

    /**
     * Combines a list of alternative types into a single Type. Returns null if
     * the list is empty, the lone Type if it has one element, or a UnionType /
     * NullableType otherwise.
     *
     * @param array<array-key,Type> $types
     */
    public static function combine(array $types): ?Type
    {
        $types = array_values($types);

        if ($types === []) {
            return null;
        }

        if (\count($types) === 1) {
            return $types[0];
        }

        return Type::union(...$types);
    }
}
