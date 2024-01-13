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
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;

abstract class AbstractIntegrationTest extends TestCase
{
    protected MapperTestFactory $factory;
    protected MapperInterface $mapper;
    protected MainTransformerInterface $mainTransformer;
    protected TypeResolverInterface $typeResolver;

    public function setUp(): void
    {
        $this->factory = new MapperTestFactory(
            additionalTransformers: $this->getAdditionalTransformers()
        );
        $this->mapper = $this->factory->getMapper();
        $this->mainTransformer = $this->factory->getMainTransformer();
        $this->typeResolver = $this->factory->getTypeResolver();
    }

    /**
     * @return array<string,TransformerInterface>
     */
    protected function getAdditionalTransformers(): array
    {
        return [];
    }
}
