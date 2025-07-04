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

use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Proxy\ProxyMetadataFactoryInterface;

/**
 * @internal
 */
final readonly class PhpProxyFactory implements ProxyFactoryInterface
{
    public function __construct(
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
        $properties = $classMetadata->getPropertyMetadatas($eagerProperties);

        $reflectionClass = new \ReflectionClass($class);

        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress UndefinedMethod
         * @var T
         */
        $proxy = $reflectionClass->newLazyGhost($initializer);

        foreach ($properties as $property) {
            $scopeClass = $property->getScopeClass();
            $name = $property->getName();

            $scopeReflectionClass = new \ReflectionClass($scopeClass);

            /** @psalm-suppress UndefinedMethod */
            $scopeReflectionClass
                ->getProperty($name)
                ->skipLazyInitialization($proxy);
        }

        return $proxy;
    }
}
