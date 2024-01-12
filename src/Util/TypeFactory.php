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
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Symfony\Component\PropertyInfo\Type;

/**
 * Convenience factory to instantiate Type objects
 */
class TypeFactory
{
    private function __construct()
    {
    }

    /**
     * Convert a string type notation to a Type object. Currently does not do
     * anything fancy. Just supports the built-in types and class names.
     *
     * @return Type|MixedType
     */
    public static function fromString(string $string): Type|MixedType
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
        $string = preg_replace('/^\?/', '', $string) ?? throw new InvalidArgumentException(sprintf('"%s" does not appear to be a valid type.', $string));

        // remove generics notation
        $string = preg_replace('/<.*>/', '', $string) ?? throw new InvalidArgumentException(sprintf('"%s" does not appear to be a valid type.', $string));

        if (!TypeCheck::nameExists($string)) {
            throw new InvalidArgumentException(sprintf('"%s" does not appear to be a valid type.', $string));
        }

        return self::objectOfClass($string);
    }

    public static function fromBuiltIn(string $builtIn): Type
    {
        return new Type(
            builtinType: $builtIn
        );
    }

    public static function mixed(): MixedType
    {
        return MixedType::instance();
    }

    public static function null(): Type
    {
        return new Type(
            builtinType: 'null'
        );
    }

    public static function scalar(string $type): Type
    {
        if (!in_array($type, ['string', 'int', 'float', 'bool'], true)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid scalar type.', $type));
        }

        return new Type(
            builtinType: $type
        );
    }

    public static function string(): Type
    {
        return new Type(
            builtinType: 'string'
        );
    }

    public static function int(): Type
    {
        return new Type(
            builtinType: 'int'
        );
    }

    public static function float(): Type
    {
        return new Type(
            builtinType: 'float'
        );
    }

    public static function bool(): Type
    {
        return new Type(
            builtinType: 'bool'
        );
    }

    public static function resource(): Type
    {
        return new Type(
            builtinType: 'resource'
        );
    }

    public static function true(): Type
    {
        return new Type(
            builtinType: 'true'
        );
    }

    public static function false(): Type
    {
        return new Type(
            builtinType: 'false'
        );
    }

    public static function callable(): Type
    {
        return new Type(
            builtinType: 'callable'
        );
    }

    public static function array(): Type
    {
        return new Type(
            builtinType: 'array',
        );
    }

    public static function iterable(): Type
    {
        return new Type(
            builtinType: 'iterable',
        );
    }

    public static function arrayWithKeyValue(
        null|Type $keyType,
        null|Type $valueType
    ): Type {
        return new Type(
            builtinType: 'array',
            collection: true,
            collectionKeyType: $keyType,
            collectionValueType: $valueType
        );
    }

    public static function arrayOfObject(string $class): Type
    {
        if (!TypeCheck::nameExists($class)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid class.', $class));
        }

        return new Type(
            builtinType: 'array',
            collection: true,
            collectionValueType: self::objectOfClass($class)
        );
    }

    public static function object(): Type
    {
        return new Type(
            builtinType: 'object',
        );
    }

    public static function objectOfClass(string $class): Type
    {
        if (!TypeCheck::nameExists($class)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid class.', $class));
        }

        return new Type(
            builtinType: 'object',
            class: $class
        );
    }

    public static function objectWithKeyValue(
        ?string $class,
        null|Type $keyType,
        null|Type $valueType
    ): Type {
        if ($class === null || !TypeCheck::nameExists($class)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid class.', $class === null ? 'null' : $class));
        }

        return new Type(
            builtinType: 'object',
            class: $class,
            collection: true,
            collectionKeyType: $keyType,
            collectionValueType: $valueType
        );
    }
}
