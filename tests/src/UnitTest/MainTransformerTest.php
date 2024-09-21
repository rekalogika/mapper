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
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Transformer\Implementation\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ScalarToScalarTransformer;
use Symfony\Component\Clock\DatePoint;

class MainTransformerTest extends FrameworkTestCase
{
    public function testMapping(): void
    {
        $mappingFactory = $this->get('rekalogika.mapper.mapping_factory');
        $this->assertInstanceOf(MappingFactoryInterface::class, $mappingFactory);

        $mapping = $mappingFactory->getMapping();

        $this->assertStringContainsString(
            ScalarToScalarTransformer::class,
            $mapping->getMappingBySourceAndTarget(['string'], ['string'])[0]->getId(),
        );

        $this->assertStringContainsString(
            DateTimeTransformer::class,
            $mapping->getMappingBySourceAndTarget(['string'], [DatePoint::class])[0]->getId(),
        );
    }
}
