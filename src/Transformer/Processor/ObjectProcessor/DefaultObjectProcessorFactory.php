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

namespace Rekalogika\Mapper\Transformer\Processor\ObjectProcessor;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\Processor\ObjectProcessorFactoryInterface;
use Rekalogika\Mapper\Transformer\Processor\ObjectProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal
 */
final class DefaultObjectProcessorFactory implements ObjectProcessorFactoryInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private readonly ContainerInterface $propertyMapperLocator,
        private readonly SubMapperFactoryInterface $subMapperFactory,
        private readonly ProxyFactoryInterface $proxyFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly LoggerInterface $logger,
    ) {}

    public function getObjectProcessor(
        ObjectToObjectMetadata $metadata,
    ): ObjectProcessorInterface {
        return new ObjectProcessor(
            metadata: $metadata,
            mainTransformer: $this->getMainTransformer(),
            propertyMapperLocator: $this->propertyMapperLocator,
            subMapperFactory: $this->subMapperFactory,
            proxyFactory: $this->proxyFactory,
            propertyAccessor: $this->propertyAccessor,
            logger: $this->logger,
        );
    }
}
