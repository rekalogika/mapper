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
use Psr\Log\LoggerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Debug\MapperDataCollector;
use Rekalogika\Mapper\Debug\TraceableTransformer;
use Rekalogika\Mapper\IterableMapperInterface;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Tests\Services\TestLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * @property MapperInterface $mapper
 * @property IterableMapperInterface $iterableMapper
 */
abstract class FrameworkTestCase extends KernelTestCase
{
    protected ?MapperInterface $mapperCache = null;

    protected ?IterableMapperInterface $iterableMapperCache = null;

    public function __get(string $name): object
    {
        if ($name === 'mapper') {
            return $this->mapperCache ??= new MapperDecorator(
                $this->get(MapperInterface::class),
                $this->getMapperContext(),
            );
        } elseif ($name === 'iterableMapper') {
            return $this->iterableMapperCache ??= new IterableMapperDecorator(
                $this->get(IterableMapperInterface::class),
                $this->getMapperContext(),
            );
        }

        throw new \BadMethodCallException();
    }

    /**
     * @template T of object
     * @param string|class-string<T> $serviceId
     * @return ($serviceId is class-string<T> ? T : object)
     */
    public function get(string $serviceId): object
    {
        $result = static::getContainer()->get($serviceId);

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        $this->assertNotNull($result);

        return $result;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->getLogger()->reset();
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

    public function getDataCollector(): MapperDataCollector
    {
        $result = $this->get('rekalogika.mapper.data_collector');

        $this->assertInstanceOf(MapperDataCollector::class, $result);

        return $result;
    }

    public function assertLogContains(string $message): void
    {
        $logger = $this->getLogger();
        $this->assertTrue($logger->isInMessage($message), 'Log message not found: ' . $message);
    }

    private function getLogger(): TestLogger
    {
        $logger = static::getContainer()->get(LoggerInterface::class);
        $this->assertInstanceOf(TestLogger::class, $logger);

        return $logger;
    }
}
