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

use DaveLiddament\PhpLanguageExtensions\Friend;
use Rekalogika\Mapper\Attribute\MapperAttributeInterface;
use Rekalogika\Mapper\Debug\TraceData;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\MainTransformer\Exception\TransformerReturnsUnexpectedValueException;
use Rekalogika\Mapper\Tests\UnitTest\Util\TypeUtil2Test;
use Rekalogika\Mapper\Tests\UnitTest\Util\TypeUtilTest;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\TypeResolver\Implementation\TypeResolver;
use Symfony\Component\PropertyInfo\Type;

final readonly class TypeUtil
{
    private function __construct()
    {
    }

    /**
     * Simple Type is a type that is not nullable, and does not have more
     * than one key type or value type.
     */
    #[Friend(TypeResolver::class, TypeUtilTest::class)]
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

            if (\count($keyTypes) > 1) {
                return false;
            }

            if (\count($valueTypes) > 1) {
                return false;
            }

            $keyTypeIsSimple = $keyTypes === []
                || self::isSimpleType($keyTypes[0]);

            $valueTypeIsSimple = $valueTypes === []
                || self::isSimpleType($valueTypes[0]);

            return $keyTypeIsSimple && $valueTypeIsSimple;
        }

        return true;
    }

    /**
     * Gets all the possible simple types from a Type
     *
     * @return array<int,Type>
     */
    #[Friend(TypeResolver::class)]
    public static function getSimpleTypes(Type $type, bool $withParents = false): array
    {
        return self::getTypePermutations([$type], withParents: $withParents);
    }

    /**
     * Generates all the possible simple type permutations from an array of
     * Types
     *
     * @param array<array-key,Type> $types
     * @return array<int,Type>
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

                    foreach (ClassUtil::getAllClassesFromObject($typeClass) as $class) {
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

            if ($keyTypes === []) {
                $keyTypes = [null];
            }

            if ($valueTypes === []) {
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
     * @param null|Type|MixedType|array<array-key,Type|MixedType> $type
     */
    public static function getDebugType(null|Type|MixedType|array $type): string
    {
        if ($type === null) {
            return 'null';
        }

        if (\is_array($type)) {
            if ($type === []) {
                return 'mixed';
            }

            $typeStrings = [];
            foreach ($type as $t) {
                $typeStrings[] = TypeUtil::getTypeString($t);
            }

            return implode('|', $typeStrings);
        }

        return TypeUtil::getTypeString($type);
    }

    #[Friend(
        TypeResolver::class,
        TransformerReturnsUnexpectedValueException::class,
        TypeUtilTest::class,
        TraceData::class
    )]
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

            if ($keyTypes !== []) {
                $keyTypesString = [];
                foreach ($keyTypes as $keyType) {
                    $keyTypesString[] = self::getTypeString($keyType);
                }

                $keyTypesString = implode('|', $keyTypesString);
            } else {
                $keyTypesString = 'mixed';
            }

            $valueTypes = $type->getCollectionValueTypes();

            if ($valueTypes !== []) {
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
     * @param Type|MixedType|array<int,Type|MixedType> $type
     */
    public static function getTypeStringHtml(Type|MixedType|array $type): string
    {
        if (\is_array($type)) {
            if ($type === []) {
                return 'mixed';
            }

            $typeStrings = [];
            foreach ($type as $t) {
                $typeStrings[] = self::getTypeStringHtml($t);
            }

            return implode('|', $typeStrings);
        }

        if ($type instanceof MixedType) {
            return 'mixed';
        }

        $typeString = $type->getBuiltinType();
        if ($typeString === 'object') {
            $typeString = $type->getClassName();

            if (null === $typeString) {
                $typeString = 'object';
            } else {
                $shortClassName = preg_replace('/^.*\\\\/', '', $typeString) ?? $typeString;
                $typeString = sprintf(
                    '<abbr title="%s">%s</abbr>',
                    htmlspecialchars($typeString),
                    htmlspecialchars($shortClassName)
                );
            }
        }

        if ($type->isCollection()) {
            $keyTypes = $type->getCollectionKeyTypes();

            if ($keyTypes !== []) {
                $keyTypesString = [];
                foreach ($keyTypes as $keyType) {
                    $keyTypesString[] = self::getTypeStringHtml($keyType);
                }

                $keyTypesString = implode('|', $keyTypesString);
            } else {
                $keyTypesString = 'mixed';
            }

            $valueTypes = $type->getCollectionValueTypes();

            if ($valueTypes !== []) {
                $valueTypesString = [];
                foreach ($valueTypes as $valueType) {
                    $valueTypesString[] = self::getTypeStringHtml($valueType);
                }

                $valueTypesString = implode('|', $valueTypesString);
            } else {
                $valueTypesString = 'mixed';
            }

            $typeString .= sprintf('&lt;%s,%s&gt;', $keyTypesString, $valueTypesString);
        }

        return $typeString;
    }

    /**
     * @return array<int,string>
     */
    #[Friend(TypeResolver::class, TypeUtil2Test::class)]
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
     * @return array<int,Type>
     */
    private static function getAttributesFromType(
        Type|MixedType $type
    ): array {
        if ($type instanceof MixedType) {
            return [];
        }

        $class = $type->getClassName();

        if ($class === null) {
            return [];
        }

        if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
            return [];
        }

        $attributes = (new \ReflectionClass($class))
            ->getAttributes(
                MapperAttributeInterface::class,
                \ReflectionAttribute::IS_INSTANCEOF
            );

        $attributeTypes = [];

        foreach ($attributes as $attribute) {
            $attributeTypes[] = TypeFactory::objectOfClass($attribute->getName());
        }

        return $attributeTypes;
    }

    /**
     * @param Type $type
     * @return array<int,string>
     */
    #[Friend(TypeResolver::class)]
    public static function getAttributesTypeStrings(
        Type|MixedType $type
    ): array {
        $attributes = self::getAttributesFromType($type);

        $attributeTypeStrings = [];

        foreach ($attributes as $attribute) {
            $attributeTypeStrings[] = self::getTypeString($attribute);
        }

        return $attributeTypeStrings;
    }
}
