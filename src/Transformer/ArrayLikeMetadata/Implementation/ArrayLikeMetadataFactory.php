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

namespace Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Implementation;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Rekalogika\Mapper\CollectionInterface;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @internal
 */
final readonly class ArrayLikeMetadataFactory implements ArrayLikeMetadataFactoryInterface
{
    #[\Override]
    public function createArrayLikeMetadata(
        Type $sourceType,
        Type $targetType,
    ): ArrayLikeMetadata {
        $sourceMemberKeyTypes = self::extractMemberKeyTypes($sourceType);
        $targetMemberKeyTypes = self::extractMemberKeyTypes($targetType);

        if ($sourceMemberKeyTypes === []) {
            $sourceMemberKeyTypes = [
                TypeFactory::int(),
                TypeFactory::string(),
            ];
        }

        if ($targetMemberKeyTypes === []) {
            $targetMemberKeyTypes = [
                TypeFactory::int(),
                TypeFactory::string(),
            ];
        }

        $sourceMemberValueTypes = self::extractMemberValueTypes($sourceType);
        $targetMemberValueTypes = self::extractMemberValueTypes($targetType);

        $isSourceArray = TypeCheck::isArray($sourceType);
        $isTargetArray = TypeCheck::isArray($targetType);

        $sourceClass = self::extractClassName($sourceType);
        if ($sourceClass !== null && (!class_exists($sourceClass) && !interface_exists($sourceClass))) {
            throw new InvalidArgumentException(\sprintf('Source class "%s" does not exist', $sourceClass));
        }

        $targetClass = self::extractClassName($targetType);
        if ($targetClass !== null && (!class_exists($targetClass) && !interface_exists($targetClass))) {
            throw new InvalidArgumentException(\sprintf('Target class "%s" does not exist', $targetClass));
        }

        $sourceMemberKeyTypeCanBeInt = false;
        $sourceMemberKeyTypeCanBeString = false;
        $sourceMemberKeyTypeCanBeOtherThanIntOrString = false;

        foreach ($sourceMemberKeyTypes as $sourceMemberKeyType) {
            if (TypeCheck::isInt($sourceMemberKeyType)) {
                $sourceMemberKeyTypeCanBeInt = true;
            } elseif (TypeCheck::isString($sourceMemberKeyType)) {
                $sourceMemberKeyTypeCanBeString = true;
            } else {
                $sourceMemberKeyTypeCanBeOtherThanIntOrString = true;
            }
        }

        $targetMemberKeyTypeCanBeInt = false;
        $targetMemberKeyTypeCanBeString = false;
        $targetMemberKeyTypeCanBeOtherThanIntOrString = false;

        foreach ($targetMemberKeyTypes as $targetMemberKeyType) {
            if (TypeCheck::isInt($targetMemberKeyType)) {
                $targetMemberKeyTypeCanBeInt = true;
            } elseif (TypeCheck::isString($targetMemberKeyType)) {
                $targetMemberKeyTypeCanBeString = true;
            } else {
                $targetMemberKeyTypeCanBeOtherThanIntOrString = true;
            }
        }

        $targetMemberValueIsUntyped = $targetMemberValueTypes === []
            || (\count($targetMemberValueTypes) === 1 && TypeCheck::isMixed($targetMemberValueTypes[0]));

        if ($targetMemberValueIsUntyped) {
            $targetMemberValueTypes = [];
        }

        // determine if target can be lazy

        $targetCanBeLazy = !$isTargetArray
            && !$sourceMemberKeyTypeCanBeOtherThanIntOrString
            && !$targetMemberKeyTypeCanBeOtherThanIntOrString
            && (
                $targetClass === \ArrayAccess::class
                || $targetClass === \Traversable::class
                || $targetClass === CollectionInterface::class
                || $targetClass === Collection::class
                || $targetClass === ReadableCollection::class
            );

        return new ArrayLikeMetadata(
            sourceType: $sourceType,
            targetType: $targetType,
            isSourceArray: $isSourceArray,
            sourceClass: $sourceClass,
            isTargetArray: $isTargetArray,
            targetClass: $targetClass,
            targetCanBeLazy: $targetCanBeLazy,
            sourceMemberKeyTypes: $sourceMemberKeyTypes,
            sourceMemberValueTypes: $sourceMemberValueTypes,
            targetMemberKeyTypes: $targetMemberKeyTypes,
            targetMemberValueTypes: $targetMemberValueTypes,
            sourceMemberKeyCanBeInt: $sourceMemberKeyTypeCanBeInt,
            sourceMemberKeyCanBeString: $sourceMemberKeyTypeCanBeString,
            sourceMemberKeyCanBeOtherThanIntOrString: $sourceMemberKeyTypeCanBeOtherThanIntOrString,
            targetMemberKeyCanBeInt: $targetMemberKeyTypeCanBeInt,
            targetMemberKeyCanBeString: $targetMemberKeyTypeCanBeString,
            targetMemberKeyCanBeOtherThanIntOrString: $targetMemberKeyTypeCanBeOtherThanIntOrString,
            targetMemberValueIsUntyped: $targetMemberValueIsUntyped,
        );
    }

    /**
     * @return list<Type>
     */
    private static function extractMemberKeyTypes(Type $type): array
    {
        if (!$type instanceof CollectionType) {
            return [];
        }

        return self::flattenUnion($type->getCollectionKeyType());
    }

    /**
     * @return list<Type>
     */
    private static function extractMemberValueTypes(Type $type): array
    {
        if (!$type instanceof CollectionType) {
            return [];
        }

        return self::flattenUnion($type->getCollectionValueType());
    }

    /**
     * @return list<Type>
     */
    private static function flattenUnion(Type $type): array
    {
        if ($type instanceof UnionType) {
            return $type->getTypes();
        }

        return [$type];
    }

    /**
     * @return ?class-string
     */
    private static function extractClassName(Type $type): ?string
    {
        $unwrapped = $type;
        while ($unwrapped instanceof WrappingTypeInterface) {
            $unwrapped = $unwrapped->getWrappedType();
        }

        if ($unwrapped instanceof ObjectType) {
            /** @var class-string */
            return $unwrapped->getClassName();
        }

        if ($unwrapped instanceof BuiltinType && $unwrapped->getTypeIdentifier() === TypeIdentifier::OBJECT) {
            return null;
        }

        return null;
    }
}
