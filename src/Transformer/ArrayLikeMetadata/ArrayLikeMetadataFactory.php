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

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class ArrayLikeMetadataFactory implements ArrayLikeMetadataFactoryInterface
{
    public function createArrayLikeMetadata(Type $targetType): ArrayLikeMetadata
    {
        $memberKeyTypes = $targetType->getCollectionKeyTypes();

        if (count($memberKeyTypes) === 0) {
            $memberKeyTypes = [
                TypeFactory::int(),
                TypeFactory::string(),
            ];
        }

        $memberValueTypes = $targetType->getCollectionValueTypes();

        $isTargetArray = TypeCheck::isArray($targetType);
        $class = $targetType->getClassName();
        if ($class !== null) {
            if (!class_exists($class) && !interface_exists($class)) {
                throw new InvalidArgumentException(sprintf('Target class "%s" does not exist', $class));
            }
        }

        $memberKeyTypeCanBeInt = false;
        $memberKeyTypeCanBeString = false;
        $memberKeyTypeCanBeOtherThanIntOrString = false;

        foreach ($memberKeyTypes as $memberKeyType) {
            if (TypeCheck::isInt($memberKeyType)) {
                $memberKeyTypeCanBeInt = true;
            } elseif (TypeCheck::isString($memberKeyType)) {
                $memberKeyTypeCanBeString = true;
            } else {
                $memberKeyTypeCanBeOtherThanIntOrString = true;
            }
        }

        $memberValueIsUntyped = count($memberValueTypes) === 0;

        return new ArrayLikeMetadata(
            targetType: $targetType,
            isTargetArray: $isTargetArray,
            targetClass: $class,
            targetMemberKeyTypes: $memberKeyTypes,
            targetMemberValueTypes: $memberValueTypes,
            targetMemberKeyCanBeInt: $memberKeyTypeCanBeInt,
            targetMemberKeyCanBeString: $memberKeyTypeCanBeString,
            targetMemberKeyCanBeIntOnly: $memberKeyTypeCanBeInt && !$memberKeyTypeCanBeString,
            targetMemberKeyCanBeOtherThanIntOrString: $memberKeyTypeCanBeOtherThanIntOrString,
            targetMemberValueIsUntyped: $memberValueIsUntyped,
        );
    }
}
