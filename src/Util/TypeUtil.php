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
use Rekalogika\Mapper\Tests\IntegrationTest\MapPropertyPathTest;
use Rekalogika\Mapper\Tests\UnitTest\Util\TypeUtil2Test;
use Rekalogika\Mapper\Tests\UnitTest\Util\TypeUtilTest;
use Rekalogika\Mapper\Transformer\Exception\NullSourceButMandatoryTargetException;
use Rekalogika\Mapper\TypeResolver\Implementation\TypeResolver;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;

final readonly class TypeUtil
{
    private function __construct() {}

    /**
     * Simple Type is a type that is not nullable, not a union, not an
     * intersection, not bare iterable, and whose collection key/value generics
     * (if any) are themselves simple.
     */
    #[Friend(TypeResolver::class, TypeUtilTest::class)]
    public static function isSimpleType(Type $type): bool
    {
        if (
            $type instanceof NullableType
            || $type instanceof UnionType
            || $type instanceof IntersectionType
        ) {
            return false;
        }

        // iterable is Traversable|array — not simple
        if (TypeCheck::isIterable($type)) {
            return false;
        }

        if ($type instanceof CollectionType) {
            $wrapped = $type->getWrappedType();
            if ($wrapped instanceof GenericType) {
                foreach ($wrapped->getVariableTypes() as $variableType) {
                    if (!self::isSimpleType($variableType)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Gets all the possible simple types from a Type.
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
     * Types.
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
                if ($type instanceof NullableType) {
                    $hasNullable = true;
                    $type = $type->getWrappedType();
                }

                if (TypeCheck::isIterable($type)) {
                    throw new InvalidArgumentException(
                        'Iterable are not supported in source or target specification. Use Traversable or array instead.',
                    );
                }

                $variants = $type instanceof UnionType ? $type->getTypes() : [$type];

                foreach ($variants as $variant) {
                    if ($variant instanceof BuiltinType && $variant->getTypeIdentifier() === TypeIdentifier::NULL) {
                        $hasNullable = true;
                        continue;
                    }

                    $variantClass = self::getObjectClass($variant);

                    if ($variantClass !== null) {
                        foreach (ClassUtil::getAllClassesFromObject($variantClass) as $class) {
                            $newTypes[] = self::reclassify($variant, $class);
                        }

                        $newTypes[] = TypeFactory::object();
                    } else {
                        $newTypes[] = $variant;
                    }
                }
            }

            $types = $newTypes;
        }

        foreach ($types as $type) {
            if ($type instanceof NullableType) {
                $hasNullable = true;
                $type = $type->getWrappedType();
            }

            if ($type instanceof UnionType) {
                foreach ($type->getTypes() as $variant) {
                    foreach (self::getTypePermutations([$variant], $withParents) as $perm) {
                        $permutations[] = $perm;
                    }
                }
                continue;
            }

            if ($type instanceof BuiltinType && $type->getTypeIdentifier() === TypeIdentifier::NULL) {
                $hasNullable = true;
                continue;
            }

            if (!$type instanceof CollectionType) {
                $permutations[] = $type;
                continue;
            }

            $wrapped = $type->getWrappedType();

            if (!$wrapped instanceof GenericType) {
                // bare CollectionType (no key/value generics)
                if (TypeCheck::isIterable($type)) {
                    $permutations[] = TypeFactory::array();
                    $permutations[] = TypeFactory::objectOfClass(\Traversable::class);
                } else {
                    $permutations[] = $type;
                }
                continue;
            }

            // Generic CollectionType with explicit key/value
            $variableTypes = $wrapped->getVariableTypes();
            $keyType = $variableTypes[0] ?? null;
            $valueType = $variableTypes[1] ?? null;

            $keyTypes = $keyType !== null
                ? self::getTypePermutations([$keyType], $withParents)
                : [];
            $valueTypes = $valueType !== null
                ? self::getTypePermutations([$valueType], $withParents)
                : [];

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

            foreach ($keyTypes as $kp) {
                foreach ($valueTypes as $vp) {
                    if (TypeCheck::isIterable($type)) {
                        $permutations[] = TypeFactory::arrayWithKeyValue($kp, $vp);
                        $permutations[] = TypeFactory::objectWithKeyValue(\Traversable::class, $kp, $vp);
                    } else {
                        $permutations[] = self::reconstructCollection($wrapped, $kp, $vp);
                    }
                }
            }

            if ($withParents) {
                if (TypeCheck::isIterable($type)) {
                    $permutations[] = TypeFactory::array();
                    $permutations[] = TypeFactory::objectOfClass(\Traversable::class);
                } else {
                    $permutations[] = self::stripCollectionGenerics($wrapped);
                }
            }
        }

        if ($hasNullable) {
            $permutations[] = TypeFactory::null();
        }

        return $permutations;
    }

    /**
     * @param null|Type|array<array-key,Type> $type
     */
    public static function getDebugType(null|Type|array $type): string
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
                $typeStrings[] = self::getTypeString($t);
            }

            return implode('|', $typeStrings);
        }

        return self::getTypeString($type);
    }

    #[Friend(
        TypeResolver::class,
        TransformerReturnsUnexpectedValueException::class,
        TypeUtilTest::class,
        TraceData::class,
        MapPropertyPathTest::class,
        NullSourceButMandatoryTargetException::class,
    )]
    public static function getTypeString(Type $type): string
    {
        if ($type instanceof NullableType) {
            return self::getTypeString($type->getWrappedType()) . '|null';
        }

        if ($type instanceof UnionType) {
            $parts = [];
            foreach ($type->getTypes() as $variant) {
                $parts[] = self::getTypeString($variant);
            }
            return implode('|', $parts);
        }

        if ($type instanceof IntersectionType) {
            $parts = [];
            foreach ($type->getTypes() as $variant) {
                $parts[] = self::getTypeString($variant);
            }
            return implode('&', $parts);
        }

        if ($type instanceof CollectionType) {
            $wrapped = $type->getWrappedType();

            if ($wrapped instanceof GenericType) {
                $main = $wrapped->getWrappedType();
                $mainString = self::mainTypeString($main);

                $variableTypes = $wrapped->getVariableTypes();
                $keyType = $variableTypes[0] ?? null;
                $valueType = $variableTypes[1] ?? null;

                $keyString = $keyType !== null
                    ? self::getTypeString($keyType)
                    : 'mixed';

                $valueString = $valueType !== null
                    ? self::getTypeString($valueType)
                    : 'mixed';

                return \sprintf('%s<%s,%s>', $mainString, $keyString, $valueString);
            }

            return self::mainTypeString($wrapped);
        }

        if ($type instanceof BuiltinType) {
            return $type->getTypeIdentifier()->value;
        }

        if ($type instanceof ObjectType) {
            return $type->getClassName();
        }

        if ($type instanceof GenericType) {
            $main = $type->getWrappedType();
            $mainString = self::mainTypeString($main);

            $variableTypes = $type->getVariableTypes();
            $keyType = $variableTypes[0] ?? null;
            $valueType = $variableTypes[1] ?? null;

            $keyString = $keyType !== null
                ? self::getTypeString($keyType)
                : 'mixed';

            $valueString = $valueType !== null
                ? self::getTypeString($valueType)
                : 'mixed';

            return \sprintf('%s<%s,%s>', $mainString, $keyString, $valueString);
        }

        return (string) $type;
    }

    /**
     * @param Type|array<int,Type> $type
     */
    public static function getTypeStringHtml(Type|array $type): string
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

        if ($type instanceof NullableType) {
            return self::getTypeStringHtml($type->getWrappedType()) . '|null';
        }

        if ($type instanceof UnionType) {
            $parts = [];
            foreach ($type->getTypes() as $variant) {
                $parts[] = self::getTypeStringHtml($variant);
            }
            return implode('|', $parts);
        }

        if ($type instanceof IntersectionType) {
            $parts = [];
            foreach ($type->getTypes() as $variant) {
                $parts[] = self::getTypeStringHtml($variant);
            }
            return implode('&', $parts);
        }

        if ($type instanceof CollectionType) {
            $wrapped = $type->getWrappedType();

            $main = $wrapped instanceof GenericType ? $wrapped->getWrappedType() : $wrapped;
            $mainString = self::mainTypeStringHtml($main);

            if (!$wrapped instanceof GenericType) {
                return $mainString;
            }

            $variableTypes = $wrapped->getVariableTypes();
            $keyType = $variableTypes[0] ?? null;
            $valueType = $variableTypes[1] ?? null;

            $keyString = $keyType !== null
                ? self::getTypeStringHtml($keyType)
                : 'mixed';

            $valueString = $valueType !== null
                ? self::getTypeStringHtml($valueType)
                : 'mixed';

            return \sprintf('%s&lt;%s,%s&gt;', $mainString, $keyString, $valueString);
        }

        if ($type instanceof BuiltinType) {
            return $type->getTypeIdentifier()->value;
        }

        if ($type instanceof ObjectType) {
            return self::formatClassNameHtml($type->getClassName());
        }

        return (string) $type;
    }

    /**
     * @return array<int,string>
     */
    #[Friend(TypeResolver::class, TypeUtil2Test::class)]
    public static function getAllTypeStrings(
        Type $type,
        bool $withParents = false,
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
    private static function getAttributesFromType(Type $type): array
    {
        $class = self::getObjectClass($type);

        if ($class === null) {
            return [];
        }

        if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
            return [];
        }

        $attributes = (new \ReflectionClass($class))
            ->getAttributes(
                MapperAttributeInterface::class,
                \ReflectionAttribute::IS_INSTANCEOF,
            );

        $attributeTypes = [];

        foreach ($attributes as $attribute) {
            /** @var class-string $attributeClass */
            $attributeClass = $attribute->getName();
            $attributeTypes[] = TypeFactory::objectOfClass($attributeClass);
        }

        return $attributeTypes;
    }

    /**
     * @return array<int,string>
     */
    #[Friend(TypeResolver::class)]
    public static function getAttributesTypeStrings(Type $type): array
    {
        $attributes = self::getAttributesFromType($type);

        $attributeTypeStrings = [];

        foreach ($attributes as $attribute) {
            $attributeTypeStrings[] = self::getTypeString($attribute);
        }

        return $attributeTypeStrings;
    }

    private static function mainTypeString(Type $type): string
    {
        if ($type instanceof ObjectType) {
            return $type->getClassName();
        }

        if ($type instanceof BuiltinType) {
            return $type->getTypeIdentifier()->value;
        }

        return (string) $type;
    }

    private static function mainTypeStringHtml(Type $type): string
    {
        if ($type instanceof ObjectType) {
            return self::formatClassNameHtml($type->getClassName());
        }

        if ($type instanceof BuiltinType) {
            return $type->getTypeIdentifier()->value;
        }

        return (string) $type;
    }

    private static function formatClassNameHtml(string $className): string
    {
        $shortClassName = preg_replace('/^.*\\\\/', '', $className) ?? $className;

        return \sprintf(
            '<abbr title="%s">%s</abbr>',
            htmlspecialchars($className),
            htmlspecialchars($shortClassName),
        );
    }

    /**
     * Extracts the underlying object class name from any Type, walking through
     * NullableType/CollectionType/GenericType wrapping. Returns null if the
     * type is not (ultimately) an object with a known class.
     *
     * @return ?class-string
     */
    private static function getObjectClass(Type $type): ?string
    {
        if ($type instanceof NullableType) {
            return self::getObjectClass($type->getWrappedType());
        }

        if ($type instanceof CollectionType) {
            return self::getObjectClass($type->getWrappedType());
        }

        if ($type instanceof GenericType) {
            return self::getObjectClass($type->getWrappedType());
        }

        if ($type instanceof ObjectType) {
            /** @var class-string */
            return $type->getClassName();
        }

        return null;
    }

    /**
     * Produces a copy of $type with its underlying object class replaced with
     * $newClass. Preserves CollectionType/GenericType wrapping.
     *
     * @param class-string $newClass
     */
    private static function reclassify(Type $type, string $newClass): Type
    {
        if ($type instanceof ObjectType) {
            return TypeFactory::objectOfClass($newClass);
        }

        if ($type instanceof CollectionType) {
            $wrapped = $type->getWrappedType();

            if ($wrapped instanceof GenericType) {
                $variableTypes = $wrapped->getVariableTypes();
                $keyType = $variableTypes[0] ?? null;
                $valueType = $variableTypes[1] ?? null;

                return TypeFactory::objectWithKeyValue($newClass, $keyType, $valueType);
            }

            return TypeFactory::objectOfClass($newClass);
        }

        return $type;
    }

    /**
     * Builds a CollectionType using the same outer Builtin/Object as $original,
     * but with the supplied key/value types.
     */
    private static function reconstructCollection(
        GenericType $original,
        ?Type $keyType,
        ?Type $valueType,
    ): Type {
        $main = $original->getWrappedType();

        if ($main instanceof ObjectType) {
            /** @var class-string $className */
            $className = $main->getClassName();

            return TypeFactory::objectWithKeyValue(
                $className,
                $keyType,
                $valueType,
            );
        }

        // Builtin (array/iterable)
        if ($main->getTypeIdentifier() === TypeIdentifier::ARRAY) {
            return TypeFactory::arrayWithKeyValue($keyType, $valueType);
        }

        if ($main->getTypeIdentifier() === TypeIdentifier::ITERABLE) {
            return Type::iterable($valueType, $keyType);
        }

        // Fallback — shouldn't happen in practice.
        return Type::collection($main, $valueType, $keyType);
    }

    /**
     * Drops the generic key/value information, returning a bare equivalent.
     */
    private static function stripCollectionGenerics(GenericType $original): Type
    {
        $main = $original->getWrappedType();

        if ($main instanceof ObjectType) {
            /** @var class-string $className */
            $className = $main->getClassName();

            return TypeFactory::objectOfClass($className);
        }

        if ($main->getTypeIdentifier() === TypeIdentifier::ARRAY) {
            return TypeFactory::array();
        }

        if ($main->getTypeIdentifier() === TypeIdentifier::ITERABLE) {
            return TypeFactory::iterable();
        }

        return $main;
    }
}
