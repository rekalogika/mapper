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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Debug\TraceableTransformer;
use Rekalogika\Mapper\MapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\VarExporter\LazyObjectInterface;

abstract class FrameworkTestCase extends TestCase
{
    private ContainerInterface $container;
    /** @psalm-suppress MissingConstructor */
    protected MapperInterface $mapper;

    public function setUp(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();
        $this->container = $kernel->getContainer();

        $this->mapper = new MapperDecorator(
            $this->get(MapperInterface::class),
            $this->getMapperContext()
        );
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

    protected function getMapperContext(): Context
    {
        return Context::create();
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

    protected function initialize(object $object): void
    {
        if ($object instanceof LazyObjectInterface) {
            $object->initializeLazyObject();
        }
    }

    private ?EntityManagerInterface $entityManager = null;

    private function doctrineInit(): EntityManagerInterface
    {
        $managerRegistry = $this->get('doctrine');
        $this->assertInstanceOf(ManagerRegistry::class, $managerRegistry);

        $entityManager = $managerRegistry->getManager();
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        return $entityManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        if ($this->entityManager !== null) {
            return $this->entityManager;
        }

        return $this->entityManager = $this->doctrineInit();
    }
}
