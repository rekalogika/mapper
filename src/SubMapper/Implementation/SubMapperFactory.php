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
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\SubMapper\SubMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

/**
 * @internal
 */
class SubMapperFactory implements SubMapperFactoryInterface
{
    public function __construct(
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    public function createSubMapper(
        MainTransformerInterface $mainTransformer,
        Context $context,
    ): SubMapperInterface {
        $subMapper = new SubMapper(
            $this->propertyTypeExtractor,
            $this->propertyAccessor,
            $context,
        );

        return $subMapper->withMainTransformer($mainTransformer);
    }
}
