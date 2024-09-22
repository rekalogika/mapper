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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Util;

use Rekalogika\Mapper\Attribute\AllowDelete;
use Rekalogika\Mapper\Attribute\AllowTargetDelete;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;

/**
 * @internal
 */
final class AllowDeleteResolver
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public static function allowDelete(
        string $sourceClass,
        string $sourceProperty,
        string $targetClass,
        string $targetProperty,
        ?PropertyReadInfo $sourceReadInfo,
        ?PropertyReadInfo $targetReadInfo,
        ?PropertyWriteInfo $targetWriteInfo,
    ): bool {
        $targetMethods = [];

        $targetGetter = $targetReadInfo !== null
            ? $targetReadInfo->getName()
            : null;

        if ($targetGetter !== null) {
            $targetMethods[] = $targetGetter;
        }

        $targetRemover =
            (
                $targetWriteInfo !== null &&
                $targetWriteInfo->getType() === PropertyWriteInfo::TYPE_ADDER_AND_REMOVER
            )
            ? $targetWriteInfo->getRemoverInfo()->getName()
            : null;

        if ($targetRemover !== null) {
            $targetMethods[] = $targetRemover;
        }

        $allowDeleteAttributes = ClassUtil::getAttributes(
            class: $targetClass,
            property: $targetProperty,
            attributeClass: AllowDelete::class,
            methods: $targetMethods,
        );

        $targetAllowsDelete = $allowDeleteAttributes !== [];

        if ($targetAllowsDelete) {
            return true;
        }

        // process the source

        $sourceMethods = [];

        $sourceGetter = $sourceReadInfo !== null
            ? $sourceReadInfo->getName()
            : null;

        if ($sourceGetter !== null) {
            $sourceMethods[] = $sourceGetter;
        }

        $allowTargetDeleteAttributes = ClassUtil::getAttributes(
            class: $sourceClass,
            property: $sourceProperty,
            attributeClass: AllowTargetDelete::class,
            methods: $sourceMethods,
        );

        return $allowTargetDeleteAttributes !== [];
    }
}
