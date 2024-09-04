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

use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
interface ArrayLikeMetadataFactoryInterface
{
    public function createArrayLikeMetadata(
        Type $sourceType,
        Type $targetType,
    ): ArrayLikeMetadata;
}
