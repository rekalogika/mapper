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
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;

/**
 * @internal
 */
final readonly class ProxyFactory implements ProxyFactoryInterface
{
    public function __construct(
        private VarExporterProxyFactory $varExporterProxyFactory,
        private PhpProxyFactory $phpProxyFactory,
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
        // to preserve old behavior
        $reflectionClass = new \ReflectionClass($class);

        if (!$reflectionClass->isInstantiable()) {
            throw new ClassNotInstantiableException($class);
        }

        if (PHP_VERSION_ID >= 80400) {
            return $this->phpProxyFactory->createProxy(
                $class,
                $initializer,
                $eagerProperties,
            );
        } else {
            return $this->varExporterProxyFactory->createProxy(
                $class,
                $initializer,
                $eagerProperties,
            );
        }
    }
}
