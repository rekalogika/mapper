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

namespace Rekalogika\Mapper\Transformer\ArrayLikeMetadata;

use Rekalogika\Mapper\ArrayInterface;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class ArrayLikeMetadataFactory implements ArrayLikeMetadataFactoryInterface
{
    public function createArrayLikeMetadata(
        Type $sourceType,
        Type $targetType
    ): ArrayLikeMetadata {
        $targetMemberKeyTypes = $targetType->getCollectionKeyTypes();

        if (count($targetMemberKeyTypes) === 0) {
            $targetMemberKeyTypes = [
                TypeFactory::int(),
                TypeFactory::string(),
            ];
        }

        $targetMemberValueTypes = $targetType->getCollectionValueTypes();

        $isSourceArray = TypeCheck::isArray($sourceType);
        $isTargetArray = TypeCheck::isArray($targetType);

        $sourceClass = $sourceType->getClassName();
        if ($sourceClass !== null) {
            if (!class_exists($sourceClass) && !interface_exists($sourceClass)) {
                throw new InvalidArgumentException(sprintf('Source class "%s" does not exist', $sourceClass));
            }
        }

        $targetClass = $targetType->getClassName();
        if ($targetClass !== null) {
            if (!class_exists($targetClass) && !interface_exists($targetClass)) {
                throw new InvalidArgumentException(sprintf('Target class "%s" does not exist', $targetClass));
            }
        }

        $sourceMemberKeyTypeCanBeInt = false;
        $sourceMemberKeyTypeCanBeString = false;
        $sourceMemberKeyTypeCanBeOtherThanIntOrString = false;

        foreach ($sourceType->getCollectionKeyTypes() as $sourceMemberKeyType) {
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

        $targetMemberValueIsUntyped = count($targetMemberValueTypes) === 0;

        // determine if target can be lazy

        $targetCanBeLazy = !$isTargetArray
            && !$sourceMemberKeyTypeCanBeOtherThanIntOrString
            && !$targetMemberKeyTypeCanBeOtherThanIntOrString
            && (
                $targetClass === \ArrayAccess::class
                || $targetClass === \Traversable::class
                || $targetClass === ArrayInterface::class
            );

        return new ArrayLikeMetadata(
            sourceType: $sourceType,
            targetType: $targetType,
            isSourceArray: $isSourceArray,
            sourceClass: $sourceClass,
            isTargetArray: $isTargetArray,
            targetClass: $targetClass,
            targetCanBeLazy: $targetCanBeLazy,
            targetMemberKeyTypes: $targetMemberKeyTypes,
            targetMemberValueTypes: $targetMemberValueTypes,
            sourceMemberKeyCanBeInt: $sourceMemberKeyTypeCanBeInt,
            sourceMemberKeyCanBeString: $sourceMemberKeyTypeCanBeString,
            sourceMemberKeyCanBeIntOnly: $sourceMemberKeyTypeCanBeInt && !$sourceMemberKeyTypeCanBeString,
            sourceMemberKeyCanBeOtherThanIntOrString: $sourceMemberKeyTypeCanBeOtherThanIntOrString,
            targetMemberKeyCanBeInt: $targetMemberKeyTypeCanBeInt,
            targetMemberKeyCanBeString: $targetMemberKeyTypeCanBeString,
            targetMemberKeyCanBeIntOnly: $targetMemberKeyTypeCanBeInt && !$targetMemberKeyTypeCanBeString,
            targetMemberKeyCanBeOtherThanIntOrString: $targetMemberKeyTypeCanBeOtherThanIntOrString,
            targetMemberValueIsUntyped: $targetMemberValueIsUntyped,
        );
    }
}
