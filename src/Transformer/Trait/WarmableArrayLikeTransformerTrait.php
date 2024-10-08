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

namespace Rekalogika\Mapper\Transformer\Trait;

use Rekalogika\Mapper\CacheWarmer\WarmableArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableMainTransformerInterface;
use Rekalogika\Mapper\Context\Context;
use Symfony\Component\PropertyInfo\Type;

trait WarmableArrayLikeTransformerTrait
{
    public function warmingTransform(
        Type $sourceType,
        Type $targetType,
        Context $context,
    ): void {
        if (
            !$this->arrayLikeMetadataFactory instanceof WarmableArrayLikeMetadataFactoryInterface
        ) {
            return;
        }

        // get metadata & warm it

        try {
            $metadata = $this->arrayLikeMetadataFactory
                ->warmingCreateArrayLikeMetadata($sourceType, $targetType);
        } catch (\Throwable) {
            return;
        }

        // ensure main transformer is warmable

        $mainTransformer = $this->getMainTransformer();

        if (!$mainTransformer instanceof WarmableMainTransformerInterface) {
            return;
        }

        // warm source key to target key mapping

        $sourceMemberKeyTypes = $metadata->getSourceMemberKeyTypes();
        $targetMemberKeyTypes = $metadata->getTargetMemberKeyTypes();

        foreach ($sourceMemberKeyTypes as $sourceMemberKeyType) {
            $mainTransformer->warmingTransform(
                [$sourceMemberKeyType],
                $targetMemberKeyTypes,
                $context,
            );
        }

        // warm source value to target value mapping

        $sourceMemberValueTypes = $metadata->getSourceMemberValueTypes();
        $targetMemberValueTypes = $metadata->getTargetMemberValueTypes();

        foreach ($sourceMemberValueTypes as $sourceMemberValueType) {
            $mainTransformer->warmingTransform(
                [$sourceMemberValueType],
                $targetMemberValueTypes,
                $context,
            );
        }
    }

    public function isWarmable(): bool
    {
        return true;
    }
}
