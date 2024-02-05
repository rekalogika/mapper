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

namespace Rekalogika\Mapper\Tests\Common;

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Debug\TraceableTransformer;
use Rekalogika\Mapper\MapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

abstract class AbstractFrameworkTest extends TestCase
{
    private ContainerInterface $container;
    /** @psalm-suppress MissingConstructor */
    protected MapperInterface $mapper;

    public function setUp(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();
        $this->container = $kernel->getContainer();
        $this->mapper = $this->get(MapperInterface::class);
    }

    /**
     * @template T of object
     * @param string|class-string<T> $serviceId
     * @return ($serviceId is class-string<T> ? T : object)
     */
    public function get(string $serviceId): object
    {
        try {
            $result = $this->container->get('test.' . $serviceId);
        } catch (ServiceNotFoundException) {
            /** @psalm-suppress PossiblyNullReference */
            $result = $this->container->get($serviceId);
        }


        if (class_exists($serviceId) || interface_exists($serviceId)) {
            $this->assertInstanceOf($serviceId, $result);
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        $this->assertNotNull($result);

        return $result;
    }

    /**
     * @param class-string $class
     */
    protected function assertTransformerInstanceOf(string $class, object $transformer): void
    {
        if ($transformer instanceof TraceableTransformer) {
            $transformer = $transformer->getDecorated();
        }

        $this->assertInstanceOf($class, $transformer);
    }
}
