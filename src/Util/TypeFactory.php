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
use Rekalogika\Mapper\Transformer\MixedType;
use Symfony\Component\PropertyInfo\Type;

/**
 * Convenience factory to instantiate Type objects
 */
final class TypeFactory
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

    public static function mixed(): MixedType
    {
        return MixedType::instance();
    }

    private static ?Type $nullInstance = null;

    public static function null(): Type
    {
        if (self::$nullInstance === null) {
            self::$nullInstance = new Type(
                builtinType: 'null'
            );
        }

        return self::$nullInstance;
    }

    public static function scalar(string $type): Type
    {
        return match ($type) {
            'string' => self::string(),
            'int' => self::int(),
            'float' => self::float(),
            'bool' => self::bool(),
            default => throw new InvalidArgumentException(sprintf('"%s" is not a valid scalar type.', $type)),
        };
    }

    private static ?Type $stringInstance = null;

    public static function string(): Type
    {
        if (self::$stringInstance === null) {
            self::$stringInstance = new Type(
                builtinType: 'string'
            );
        }

        return self::$stringInstance;
    }

    private static ?Type $intInstance = null;

    public static function int(): Type
    {
        if (self::$intInstance === null) {
            self::$intInstance = new Type(
                builtinType: 'int'
            );
        }

        return self::$intInstance;
    }

    private static ?Type $floatInstance = null;

    public static function float(): Type
    {
        if (self::$floatInstance === null) {
            self::$floatInstance = new Type(
                builtinType: 'float'
            );
        }

        return self::$floatInstance;
    }

    private static ?Type $boolInstance = null;

    public static function bool(): Type
    {
        if (self::$boolInstance === null) {
            self::$boolInstance = new Type(
                builtinType: 'bool'
            );
        }

        return self::$boolInstance;
    }

    private static ?Type $resourceInstance = null;

    public static function resource(): Type
    {
        if (self::$resourceInstance === null) {
            self::$resourceInstance = new Type(
                builtinType: 'resource'
            );
        }

        return self::$resourceInstance;
    }

    private static ?Type $trueInstance = null;

    public static function true(): Type
    {
        if (self::$trueInstance === null) {
            self::$trueInstance = new Type(
                builtinType: 'true'
            );
        }

        return self::$trueInstance;
    }

    private static ?Type $falseInstance = null;

    public static function false(): Type
    {
        if (self::$falseInstance === null) {
            self::$falseInstance = new Type(
                builtinType: 'false'
            );
        }

        return self::$falseInstance;
    }

    private static ?Type $callableInstance = null;

    public static function callable(): Type
    {
        if (self::$callableInstance === null) {
            self::$callableInstance = new Type(
                builtinType: 'callable'
            );
        }

        return self::$callableInstance;
    }

    private static ?Type $arrayInstance = null;

    public static function array(): Type
    {
        if (self::$arrayInstance === null) {
            self::$arrayInstance = new Type(
                builtinType: 'array'
            );
        }

        return self::$arrayInstance;
    }

    private static ?Type $iterableInstance = null;

    public static function iterable(): Type
    {
        if (self::$iterableInstance === null) {
            self::$iterableInstance = new Type(
                builtinType: 'iterable'
            );
        }

        return self::$iterableInstance;
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

    /**
     * @var array<class-string,Type>
     */
    private static array $instancesOfArrayOfObject = [];

    /**
     * @param class-string $class
     */
    public static function arrayOfObject(string $class): Type
    {
        if (isset(self::$instancesOfArrayOfObject[$class])) {
            return self::$instancesOfArrayOfObject[$class];
        }

        if (!TypeCheck::nameExists($class)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid class.', $class));
        }

        return self::$instancesOfArrayOfObject[$class] = new Type(
            builtinType: 'array',
            collection: true,
            collectionValueType: self::objectOfClass($class)
        );
    }

    private static ?Type $objectInstance = null;

    public static function object(): Type
    {
        if (self::$objectInstance === null) {
            self::$objectInstance = new Type(
                builtinType: 'object'
            );
        }

        return self::$objectInstance;
    }

    /**
     * @var array<class-string,Type>
     */
    private static array $instancesOfObjectOfClass = [];

    public static function objectOfClass(string $class): Type
    {
        if (isset(self::$instancesOfObjectOfClass[$class])) {
            return self::$instancesOfObjectOfClass[$class];
        }

        if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid class.', $class));
        }

        return self::$instancesOfObjectOfClass[$class] = new Type(
            builtinType: 'object',
            class: $class
        );
    }

    public static function objectWithKeyValue(
        string $class,
        null|Type $keyType,
        null|Type $valueType
    ): Type {
        $type = clone self::objectOfClass($class);
        $reflectionClass = new \ReflectionClass($type);

        $collectionReflection = $reflectionClass->getProperty('collection');
        $collectionReflection->setAccessible(true);
        $collectionReflection->setValue($type, true);

        $collectionKeyTypeReflection = $reflectionClass->getProperty('collectionKeyType');
        $collectionKeyTypeReflection->setAccessible(true);
        $collectionKeyTypeReflection->setValue($type, [$keyType]);

        $collectionValueTypeReflection = $reflectionClass->getProperty('collectionValueType');
        $collectionValueTypeReflection->setAccessible(true);
        $collectionValueTypeReflection->setValue($type, [$valueType]);

        return $type;
    }
}
