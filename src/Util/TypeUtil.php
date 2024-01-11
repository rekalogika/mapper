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
use Rekalogika\Mapper\Model\MixedType;
use Symfony\Component\PropertyInfo\Type;

class TypeUtil
{
    private function __construct()
    {
    }

    /**
     * Simple Type is a type that is not nullable, and does not have more
     * than one key type or value type.
     */
    public static function isSimpleType(Type $type): bool
    {
        if ($type->isNullable()) {
            return false;
        }

        // iterable is Traversable|array
        if (TypeCheck::isIterable($type)) {
            return false;
        }

        if ($type->isCollection()) {
            $keyTypes = $type->getCollectionKeyTypes();
            $valueTypes = $type->getCollectionValueTypes();

            if (count($keyTypes) != 1) {
                return false;
            }

            if (count($valueTypes) != 1) {
                return false;
            }

            return self::isSimpleType($keyTypes[0])
                && self::isSimpleType($valueTypes[0]);
        }

        return true;
    }

    /**
     * Gets all the possible simple types from a Type
     *
     * @param Type|array<array-key,Type> $type
     * @return array<array-key,Type>
     */
    public static function getSimpleTypes(Type|array $type, bool $withParents = false): array
    {
        if (!is_array($type)) {
            $type = [$type];
        }

        return self::getTypePermutations($type, withParents: $withParents);
    }

    /**
     * Generates all the possible simple type permutations from an array of
     * Types
     *
     * @param array<array-key,Type> $types
     * @return array<array-key,Type>
     */
    private static function getTypePermutations(
        array $types,
        bool $withParents = false,
    ): array {
        $permutations = [];

        $hasNullable = false;

        if ($withParents) {
            $newTypes = [];

            foreach ($types as $type) {
                if (TypeCheck::isIterable($type)) {
                    throw new InvalidArgumentException(
                        'Iterable are not supported in source or target specification. Use Traversable or array instead.'
                    );
                }

                if (
                    TypeCheck::isObject($type)
                    && null !== $type->getClassName()
                ) {
                    /** @var class-string */
                    $typeClass = $type->getClassName();

                    foreach (self::getAllClassesFromObject($typeClass) as $class) {
                        $newTypes[] = new Type(
                            builtinType: $type->getBuiltinType(),
                            nullable: $type->isNullable(),
                            class: $class,
                            collection: $type->isCollection(),
                            collectionKeyType: $type->getCollectionKeyTypes(),
                            collectionValueType: $type->getCollectionValueTypes(),
                        );
                    }

                    $newTypes[] = new Type(
                        builtinType: 'object',
                        nullable: $type->isNullable(),
                    );
                } else {
                    $newTypes[] = $type;
                }
            }

            $types = $newTypes;
        }

        foreach ($types as $type) {
            if ($type->isNullable()) {
                $hasNullable = true;
            }

            if (!$type->isCollection()) {
                // iterable is Traversable|array
                if (TypeCheck::isIterable($type)) {
                    $permutations[] = TypeFactory::array();
                    $permutations[] = TypeFactory::objectOfClass(\Traversable::class);
                } else {
                    $permutations[] = new Type(
                        builtinType: $type->getBuiltinType(),
                        class: $type->getClassName(),
                    );
                }

                continue;
            }

            // the following is only applicable for collections

            $keyTypes = self::getTypePermutations(
                $type->getCollectionKeyTypes(),
                $withParents
            );
            $valueTypes = self::getTypePermutations(
                $type->getCollectionValueTypes(),
                $withParents
            );

            // 'mixed' key and value types
            if ($withParents) {
                $keyTypes[] = null;
                $valueTypes[] = null;
            }

            if (count($keyTypes) === 0) {
                $keyTypes = [null];
            }

            if (count($valueTypes) === 0) {
                $valueTypes = [null];
            }

            foreach ($keyTypes as $keyType) {
                foreach ($valueTypes as $valueType) {
                    // iterable is Traversable|array
                    if (TypeCheck::isIterable($type)) {
                        $permutations[] = TypeFactory::arrayWithKeyValue(
                            $keyType,
                            $valueType
                        );
                        $permutations[] = TypeFactory::objectWithKeyValue(
                            \Traversable::class,
                            $keyType,
                            $valueType
                        );
                    } else {
                        $permutations[] = new Type(
                            builtinType: $type->getBuiltinType(),
                            class: $type->getClassName(),
                            collection: true,
                            collectionKeyType: $keyType,
                            collectionValueType: $valueType,
                        );
                    }
                }
            }

            if ($withParents) {
                if (TypeCheck::isIterable($type)) {
                    $permutations[] = TypeFactory::array();
                    $permutations[] = TypeFactory::objectOfClass(\Traversable::class);
                } else {
                    $permutations[] = new Type(
                        builtinType: $type->getBuiltinType(),
                        class: $type->getClassName(),
                        collection: false,
                    );
                }
            }
        }

        if ($hasNullable) {
            $permutations[] = TypeFactory::null();
        }

        return $permutations;
    }

    /**
     * @param Type|MixedType $type
     * @return string
     */
    public static function getTypeString(Type|MixedType $type): string
    {
        if ($type instanceof MixedType) {
            return 'mixed';
        }

        $typeString = $type->getBuiltinType();
        if ($typeString === 'object') {
            $typeString = $type->getClassName();
            if (null === $typeString) {
                $typeString = 'object';
            }
        }

        if ($type->isCollection()) {
            $keyTypes = $type->getCollectionKeyTypes();

            if ($keyTypes) {
                $keyTypesString = [];
                foreach ($keyTypes as $keyType) {
                    $keyTypesString[] = self::getTypeString($keyType);
                }
                $keyTypesString = implode('|', $keyTypesString);
            } else {
                $keyTypesString = 'mixed';
            }

            $valueTypes = $type->getCollectionValueTypes();

            if ($valueTypes) {
                $valueTypesString = [];
                foreach ($valueTypes as $valueType) {
                    $valueTypesString[] = self::getTypeString($valueType);
                }
                $valueTypesString = implode('|', $valueTypesString);
            } else {
                $valueTypesString = 'mixed';
            }

            $typeString .= sprintf('<%s,%s>', $keyTypesString, $valueTypesString);
        }

        return $typeString;
    }

    /**
     * @return array<int,string>
     */
    public static function getAllTypeStrings(
        Type $type,
        bool $withParents = false
    ): array {
        $simpleTypes = self::getSimpleTypes($type, $withParents);

        $typeStrings = [];

        foreach ($simpleTypes as $simpleType) {
            $typeStrings[] = self::getTypeString($simpleType);
        }

        $typeStrings = array_unique($typeStrings);

        if ($withParents) {
            $typeStrings[] = 'mixed';
        }

        return $typeStrings;
    }

    /**
     * @param object|class-string $objectOrClass
     * @return array<int,class-string>
     */
    private static function getAllClassesFromObject(
        object|string $objectOrClass
    ): array {
        $classes = [];

        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;
        $classes[] = $class;

        foreach (class_parents($class) as $parentClass) {
            $classes[] = $parentClass;
        }

        foreach (class_implements($class) as $interface) {
            $classes[] = $interface;
        }

        return $classes;
    }
}
