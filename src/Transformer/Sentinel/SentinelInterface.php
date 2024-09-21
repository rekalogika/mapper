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

namespace Rekalogika\Mapper\Transformer\Sentinel;

/**
 * Interface to indicate the object is a sentinel value.
 *
 * @internal
 */
interface SentinelInterface
{
    /**
     * Exception related to the sentinel, if available.
     */
    public function getException(): ?\Throwable;
}
