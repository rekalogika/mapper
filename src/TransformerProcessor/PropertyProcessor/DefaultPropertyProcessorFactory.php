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
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorFactoryInterface;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal
 */
final class DefaultPropertyProcessorFactory implements PropertyProcessorFactoryInterface
{
    /**
     * @var array<string,PropertyProcessor> $cache
     */
    private array $cache = [];

    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly MainTransformerInterface $mainTransformer,
        private readonly SubMapperFactoryInterface $subMapperFactory,
        private readonly ContainerInterface $propertyMapperLocator,
    ) {}

    public function getPropertyMapper(
        PropertyMapping $metadata,
    ): PropertyProcessorInterface {
        $id = $metadata->getId();

        return $this->cache[$id] ??= new PropertyProcessor(
            metadata: $metadata,
            propertyAccessor: $this->propertyAccessor,
            mainTransformer: $this->mainTransformer,
            subMapperFactory: $this->subMapperFactory,
            propertyMapperLocator: $this->propertyMapperLocator,
        );
    }
}
