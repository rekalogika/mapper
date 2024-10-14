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

namespace Rekalogika\Mapper\TransformerProcessor\ObjectProcessor;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\TransformerProcessor\ObjectProcessorFactoryInterface;
use Rekalogika\Mapper\TransformerProcessor\ObjectProcessorInterface;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessor\DefaultPropertyProcessorFactory;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal
 */
final readonly class DefaultObjectProcessorFactory implements ObjectProcessorFactoryInterface
{
    private PropertyProcessorFactoryInterface $propertyProcessorFactory;

    public function __construct(
        private MainTransformerInterface $mainTransformer,
        private ContainerInterface $propertyMapperLocator,
        private SubMapperFactoryInterface $subMapperFactory,
        private ProxyFactoryInterface $proxyFactory,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->propertyProcessorFactory = new DefaultPropertyProcessorFactory(
            propertyAccessor: $this->propertyAccessor,
            mainTransformer: $this->mainTransformer,
            subMapperFactory: $this->subMapperFactory,
            propertyMapperLocator: $this->propertyMapperLocator,
        );
    }

    public function getObjectProcessor(
        ObjectToObjectMetadata $metadata,
    ): ObjectProcessorInterface {
        return new ObjectProcessor(
            metadata: $metadata,
            mainTransformer: $this->mainTransformer,
            propertyMapperLocator: $this->propertyMapperLocator,
            subMapperFactory: $this->subMapperFactory,
            proxyFactory: $this->proxyFactory,
            propertyAccessor: $this->propertyAccessor,
            propertyProcessorFactory: $this->propertyProcessorFactory,
        );
    }
}
