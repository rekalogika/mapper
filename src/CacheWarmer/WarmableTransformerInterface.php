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

use Rekalogika\Mapper\Context\Context;
use Symfony\Component\PropertyInfo\Type;

interface WarmableTransformerInterface
{
    public function warmingTransform(
        Type $sourceType,
        Type $targetType,
        Context $context,
    ): void;

    public function isWarmable(): bool;
}
