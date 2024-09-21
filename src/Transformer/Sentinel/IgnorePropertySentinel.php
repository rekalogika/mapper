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
 * Sentinel value to indicate the target property should be left as is.
 *
 * @internal
 */
final readonly class IgnorePropertySentinel implements SentinelInterface
{
    public function __construct(private ?\Throwable $exception = null) {}

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }
}
