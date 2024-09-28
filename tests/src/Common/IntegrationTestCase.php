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
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;

abstract class IntegrationTestCase extends TestCase
{
    protected MapperTestFactory $factory;

    protected MapperInterface $mapper;

    protected MainTransformerInterface $mainTransformer;

    protected TypeResolverInterface $typeResolver;

    protected TransformerRegistryInterface $transformerRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new MapperTestFactory(
            additionalTransformers: $this->getAdditionalTransformers(),
        );

        foreach ($this->getPropertyMappers() as $propertyMapper) {
            $this->factory->addPropertyMapper(
                sourceClass: $propertyMapper['sourceClass'],
                targetClass: $propertyMapper['targetClass'],
                property: $propertyMapper['property'],
                service: $propertyMapper['service'],
                method: $propertyMapper['method'],
                hasExistingTarget: $propertyMapper['hasExistingTarget'],
            );
        }

        $this->mapper = $this->factory->getMapper();
        $this->mainTransformer = $this->factory->getMainTransformer();
        $this->typeResolver = $this->factory->getTypeResolver();
        $this->transformerRegistry = $this->factory->getTransformerRegistry();
    }

    /**
     * @return array<string,TransformerInterface>
     */
    protected function getAdditionalTransformers(): array
    {
        return [];
    }

    /**
     * @return iterable<array{sourceClass:class-string,targetClass:class-string,property:string,service:object,method:string,hasExistingTarget:bool}>
     */
    protected function getPropertyMappers(): iterable
    {
        return [];
    }
}
