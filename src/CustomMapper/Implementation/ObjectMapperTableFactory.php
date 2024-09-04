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

use Rekalogika\Mapper\CustomMapper\ObjectMapperTable;
use Rekalogika\Mapper\CustomMapper\ObjectMapperTableFactoryInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;

/**
 * @internal
 */
final readonly class ObjectMapperTableFactory implements ObjectMapperTableFactoryInterface
{
    private ObjectMapperTable $objectMapperTable;

    public function __construct()
    {
        $this->objectMapperTable = new ObjectMapperTable();
    }

    #[\Override]
    public function createObjectMapperTable(): ObjectMapperTable
    {
        return $this->objectMapperTable;
    }

    /**
     * @param class-string                                      $sourceClass
     * @param class-string                                      $targetClass
     * @param array<int,ServiceMethodSpecification::ARGUMENT_*> $extraArguments
     */
    public function addObjectMapper(
        string $sourceClass,
        string $targetClass,
        string $serviceId,
        string $method,
        array $extraArguments = []
    ): void {
        $this->objectMapperTable->addObjectMapper(
            sourceClass: $sourceClass,
            targetClass: $targetClass,
            serviceId: $serviceId,
            method: $method,
            extraArguments: $extraArguments
        );
    }
}
