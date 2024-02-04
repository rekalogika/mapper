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

namespace Rekalogika\Mapper\Tests\UnitTest;

use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\Tests\Common\AbstractFrameworkTest;
use Rekalogika\Mapper\Transformer\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;
use Symfony\Component\Clock\DatePoint;

class MainTransformerTest extends AbstractFrameworkTest
{
    public function testMapping(): void
    {
        $mappingFactory = $this->get('test.rekalogika.mapper.mapping_factory');
        $this->assertInstanceOf(MappingFactoryInterface::class, $mappingFactory);

        $mapping = $mappingFactory->getMapping();

        $this->assertEquals(
            $mapping->getMappingBySourceAndTarget(['string'], ['string'])[0]->getClass(),
            ScalarToScalarTransformer::class
        );

        $this->assertEquals(
            $mapping->getMappingBySourceAndTarget(['string'], [DatePoint::class])[0]->getClass(),
            DateTimeTransformer::class
        );
    }
}
