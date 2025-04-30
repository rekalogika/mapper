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
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Proxy\ProxyMetadataFactoryInterface;

/**
 * @internal
 */
final readonly class DynamicPropertiesProxyFactory implements ProxyFactoryInterface
{
    public function __construct(
        private ProxyFactoryInterface $decorated,
        private ProxyMetadataFactoryInterface $proxyMetadataFactory,
    ) {}

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T):void $initializer
     * @param list<string> $eagerProperties
     * @return T
     */
    #[\Override]
    public function createProxy(
        string $class,
        $initializer,
        array $eagerProperties = [],
    ): object {
        $classMetadata = $this->proxyMetadataFactory->getMetadata($class);

        if ($classMetadata->allowsDynamicProperties()) {
            throw new ProxyNotSupportedException(
                class: $class,
                reason: 'Dynamic properties are not supported.',
            );
        }

        return $this->decorated->createProxy(
            $class,
            $initializer,
            $eagerProperties,
        );
    }
}
