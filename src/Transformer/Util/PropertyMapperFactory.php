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

namespace Rekalogika\Mapper\Transformer\Util;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal
 */
final class PropertyMapperFactory
{
    /**
     * @var array<string,PropertyMapper> $cache
     */
    private array $cache = [];

    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly MainTransformerInterface $mainTransformer,
        private readonly SubMapperFactoryInterface $subMapperFactory,
        private readonly ContainerInterface $propertyMapperLocator,
    ) {}

    public function getPropertyMapper(PropertyMapping $metadata): PropertyMapper
    {
        $id = $metadata->getId();

        return $this->cache[$id] ??= new PropertyMapper(
            $metadata,
            $this->propertyAccessor,
            $this->mainTransformer,
            $this->subMapperFactory,
            $this->propertyMapperLocator,
        );
    }

}
