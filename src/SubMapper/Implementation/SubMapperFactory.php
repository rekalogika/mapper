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

namespace Rekalogika\Mapper\SubMapper\Implementation;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\SubMapper\SubMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class SubMapperFactory implements SubMapperFactoryInterface
{
    public function __construct(
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyAccessorInterface $propertyAccessor,
        private ProxyFactoryInterface $proxyFactory,
    ) {}

    #[\Override]
    public function createSubMapper(
        MainTransformerInterface $mainTransformer,
        mixed $source,
        ?Type $targetType,
        Context $context,
    ): SubMapperInterface {
        $subMapper = new SubMapper(
            propertyTypeExtractor: $this->propertyTypeExtractor,
            propertyAccessor: $this->propertyAccessor,
            proxyFactory: $this->proxyFactory,
            source: $source,
            targetType: $targetType,
            context: $context,
        );

        return $subMapper->withMainTransformer($mainTransformer);
    }
}
