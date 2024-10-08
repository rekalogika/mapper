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

namespace Rekalogika\Mapper\CacheWarmer;

use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadata;
use Symfony\Component\PropertyInfo\Type;

interface WarmableArrayLikeMetadataFactoryInterface
{
    public function warmingCreateArrayLikeMetadata(
        Type $sourceType,
        Type $targetType,
    ): ArrayLikeMetadata;
}
