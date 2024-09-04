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

use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;

interface ProxyGeneratorInterface
{
    /**
     * @param class-string $realClass
     * @throws ProxyNotSupportedException
     */
    public function generateProxyCode(string $realClass, string $proxyClass): string;
}
