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

use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\MapperFactory;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;

class MapperTestFactory extends MapperFactory
{
    public function getTransformersIterator(): iterable
    {
        return parent::getTransformersIterator();
    }

    public function getTypeResolver(): TypeResolverInterface
    {
        return parent::getTypeResolver();
    }

    public function getMainTransformer(): MainTransformerInterface
    {
        return parent::getMainTransformer();
    }

    public function getMappingFactory(): MappingFactoryInterface
    {
        return parent::getMappingFactory();
    }

    public function getTransformerRegistry(): TransformerRegistryInterface
    {
        return parent::getTransformerRegistry();
    }

    public function getScalarToScalarTransformer(): TransformerInterface
    {
        return parent::getScalarToScalarTransformer();
    }
}
