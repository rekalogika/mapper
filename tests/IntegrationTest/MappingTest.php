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

namespace Rekalogika\Mapper\Tests\IntegrationTest;

use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;
use Rekalogika\Mapper\Util\TypeFactory;

class MappingTest extends AbstractIntegrationTest
{
    public function testScalar(): void
    {
        $searchResult = $this->transformerRegistry->findBySourceAndTargetTypes(
            sourceTypes: [
                TypeFactory::int(),
            ],
            targetTypes: [
                TypeFactory::int(),
            ],
        );

        $this->assertInstanceOf(
            ScalarToScalarTransformer::class,
            $searchResult[0]?->getTransformer()
        );
    }
}
