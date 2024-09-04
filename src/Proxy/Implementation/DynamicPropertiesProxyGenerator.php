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

namespace Rekalogika\Mapper\Proxy\Implementation;

use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Proxy\ProxyGeneratorInterface;

/**
 * Prevent proxy creation for objects with dynamic properties.
 *
 * @internal
 */
final readonly class DynamicPropertiesProxyGenerator implements ProxyGeneratorInterface
{
    public function __construct(
        private ProxyGeneratorInterface $decorated,
    ) {
    }

    #[\Override]
    public function generateProxyCode(string $realClass, string $proxyClass): string
    {
        $reflection = new \ReflectionClass($realClass);

        do {
            if ($reflection->getAttributes(\AllowDynamicProperties::class)) {
                throw new ProxyNotSupportedException($realClass, reason: 'Objects with dynamic properties do not support proxying.');
            }
        } while ($reflection = $reflection->getParentClass());


        return $this->decorated->generateProxyCode($realClass, $proxyClass);
    }
}
