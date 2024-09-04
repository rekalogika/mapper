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

namespace Rekalogika\Mapper\Proxy;

/**
 * @internal
 */
final readonly class ProxyNamer
{
    private function __construct() {}

    /**
     * @param class-string $class
     */
    public static function generateProxyClassName(string $class): string
    {
        return sprintf(
            'Rekalogika\Mapper\Generated\__CG__\%s',
            $class
        );
    }
}
