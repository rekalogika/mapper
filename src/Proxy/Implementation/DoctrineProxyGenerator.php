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
use Rekalogika\Mapper\Proxy\ProxyGeneratorInterface;
use Rekalogika\Mapper\Proxy\ProxySpecification;

/**
 * Prevent proxy creation for Doctrine entities.
 */
final class DoctrineProxyGenerator implements ProxyGeneratorInterface
{
    public function __construct(
        private ProxyGeneratorInterface $decorated,
        private ManagerRegistry $managerRegistry
    ) {
    }

    public function generateProxy(string $class): ProxySpecification
    {
        $manager = $this->managerRegistry->getManagerForClass($class);

        if ($manager) {
            throw new ProxyNotSupportedException($class, reason: 'Doctrine entities do not support proxying.');
        }

        return $this->decorated->generateProxy($class);
    }
}
