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

namespace Rekalogika\Mapper\Cache;

interface WarmableMapperInterface
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function warmMap(
        string $sourceClass,
        string $targetClass,
    ): void;
}
