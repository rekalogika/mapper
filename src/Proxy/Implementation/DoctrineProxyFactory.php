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

use Doctrine\Persistence\ManagerRegistry;
use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;

/**
 * @internal
 */
final readonly class DoctrineProxyFactory implements ProxyFactoryInterface
{
    public function __construct(
        private ProxyFactoryInterface $decorated,
        private ManagerRegistry $managerRegistry,
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
        $manager = $this->managerRegistry->getManagerForClass($class);

        if ($manager) {
            throw new ProxyNotSupportedException($class, reason: 'Doctrine entities do not support proxying.');
        }

        return $this->decorated->createProxy(
            $class,
            $initializer,
            $eagerProperties,
        );
    }
}
