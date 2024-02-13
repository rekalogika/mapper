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

namespace Rekalogika\Mapper\MapperFactory;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Simple container for non-framework use
 */
final readonly class ServiceLocator implements ContainerInterface
{
    /**
     * @param array<array-key,mixed> $services
     */
    public function __construct(
        private array $services = []
    ) {
    }

    public function get(string $id): mixed
    {
        return $this->services[$id] ?? throw new ServiceNotFoundException($id);
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
