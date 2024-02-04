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

namespace Rekalogika\Mapper\CustomMapper;

use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;

/**
 * @implements \IteratorAggregate<int,ObjectMapperTableEntry>
 */
class ObjectMapperTable implements \IteratorAggregate
{
    /**
     * @var array<class-string,array<class-string,ServiceMethodSpecification>>
     */
    private array $objectMappers = [];

    public function getIterator(): \Traversable
    {
        foreach ($this->objectMappers as $targetClass => $propertyMappers) {
            foreach ($propertyMappers as $sourceClass => $serviceMethodSpecification) {
                yield new ObjectMapperTableEntry($sourceClass, $targetClass, $serviceMethodSpecification);
            }
        }
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @param array<int,ServiceMethodSpecification::ARGUMENT_*> $extraArguments
     */
    public function addObjectMapper(
        string $sourceClass,
        string $targetClass,
        string $serviceId,
        string $method,
        array $extraArguments = []
    ): void {
        $this->objectMappers[$targetClass][$sourceClass]
            = new ServiceMethodSpecification($serviceId, $method, $extraArguments);
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function getObjectMapper(
        string $sourceClass,
        string $targetClass,
    ): ?ServiceMethodSpecification {
        if (!isset($this->objectMappers[$targetClass])) {
            return null;
        }

        $propertyMappers = $this->objectMappers[$targetClass];

        $sourceClassReflection = new \ReflectionClass($sourceClass);

        do {
            if (isset($propertyMappers[$sourceClassReflection->getName()])) {
                return $propertyMappers[$sourceClassReflection->getName()];
            }
        } while ($sourceClassReflection = $sourceClassReflection->getParentClass());

        foreach (class_implements($targetClass) as $interface) {
            if (isset($propertyMappers[$interface])) {
                return $propertyMappers[$interface];
            }
        }

        return null;
    }
}
