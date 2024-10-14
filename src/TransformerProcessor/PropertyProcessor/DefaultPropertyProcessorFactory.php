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

namespace Rekalogika\Mapper\TransformerProcessor\PropertyProcessor;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMappingMetadata;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorFactoryInterface;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal
 */
final class DefaultPropertyProcessorFactory implements
    PropertyProcessorFactoryInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly SubMapperFactoryInterface $subMapperFactory,
        private readonly ContainerInterface $propertyMapperLocator,
    ) {}

    public function getPropertyProcessor(
        PropertyMappingMetadata $metadata,
    ): PropertyProcessorInterface {
        return new PropertyProcessor(
            metadata: $metadata,
            propertyAccessor: $this->propertyAccessor,
            mainTransformer: $this->getMainTransformer(),
            subMapperFactory: $this->subMapperFactory,
            propertyMapperLocator: $this->propertyMapperLocator,
        );
    }
}
