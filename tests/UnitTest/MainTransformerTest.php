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

use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Transformer\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;
use Symfony\Component\Clock\DatePoint;

class MainTransformerTest extends AbstractIntegrationTest
{
    public function testMapping(): void
    {
        $mapping = $this->factory->getMappingFactory()->getMapping();

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
