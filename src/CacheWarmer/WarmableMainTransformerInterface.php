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

interface WarmableMainTransformerInterface
{
    /**
     * @param array<array-key,Type> $sourceTypes
     * @param array<array-key,Type> $targetTypes
     */
    public function warmingTransform(
        array $sourceTypes,
        array $targetTypes,
        Context $context,
    ): void;
}
