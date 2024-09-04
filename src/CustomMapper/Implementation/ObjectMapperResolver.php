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

namespace Rekalogika\Mapper\CustomMapper\Implementation;

use Rekalogika\Mapper\CustomMapper\Exception\ObjectMapperNotFoundException;
use Rekalogika\Mapper\CustomMapper\ObjectMapperResolverInterface;
use Rekalogika\Mapper\CustomMapper\ObjectMapperTable;
use Rekalogika\Mapper\CustomMapper\ObjectMapperTableFactoryInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;

/**
 * @internal
 */
final class ObjectMapperResolver implements ObjectMapperResolverInterface
{
    private ?ObjectMapperTable $objectMapperTable = null;

    /**
     * @var array<class-string,array<class-string,ServiceMethodSpecification>>
     */
    private array $objectMapperCache = [];

    public function __construct(
        private readonly ObjectMapperTableFactoryInterface $objectMapperTableFactory
    ) {}

    private function getObjectMapperTable(): ObjectMapperTable
    {
        if (null !== $this->objectMapperTable) {
            return $this->objectMapperTable;
        }

        return $this->objectMapperTable = $this->objectMapperTableFactory
            ->createObjectMapperTable();
    }

    #[\Override]
    public function getObjectMapper(
        string $sourceClass,
        string $targetClass
    ): ServiceMethodSpecification {
        if (isset($this->objectMapperCache[$sourceClass][$targetClass])) {
            return $this->objectMapperCache[$sourceClass][$targetClass];
        }

        $objectMapper = $this->getObjectMapperTable()
            ->getObjectMapper($sourceClass, $targetClass);

        if (null === $objectMapper) {
            throw new ObjectMapperNotFoundException($sourceClass, $targetClass);
        }

        return $this->objectMapperCache[$sourceClass][$targetClass] = $objectMapper;
    }
}
