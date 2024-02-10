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

namespace Rekalogika\Mapper\Context;

/**
 * @immutable
 */
final readonly class MapperOptions
{
    public int $manualGcInterval;

    /**
     * @param boolean $enableLazyLoading Enable or disable lazy loading.
     * @param boolean $enableTargetValueReading If disabled, values on the target side will not be read, and assumed to be null.
     * @param integer|null $manualGcInterval Performs garbage collection manually every n `MainTransformer` invocation. If not provided, and lazy loading is active, it is automatically set to 1000 to fix memory leak.
     */
    public function __construct(
        public bool $enableLazyLoading = true,
        public bool $enableTargetValueReading = true,
        ?int $manualGcInterval = null,
    ) {
        if ($manualGcInterval === null) {
            if ($enableLazyLoading) {
                $this->manualGcInterval = 1000;
            } else {
                $this->manualGcInterval = 0;
            }
        } else {
            $this->manualGcInterval = $manualGcInterval;
        }
    }
}
