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
interface ProxyFactoryInterface
{
    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T):void $initializer
     * @param list<string> $eagerProperties
     * @return T
     */
    public function createProxy(
        string $class,
        $initializer,
        array $eagerProperties = [],
    ): object;
}
