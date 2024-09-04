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

use Rekalogika\Mapper\Debug\MapperDataCollector;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\ArrayAndObject\ContainsArray;
use Rekalogika\Mapper\Tests\Fixtures\ArrayAndObject\ContainsObject;
use Rekalogika\Mapper\Transformer\Implementation\ArrayObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToObjectTransformer;

/**
 * @internal
 */
class DataCollectorTest extends FrameworkTestCase
{
    public function testDataCollector(): void
    {
        $object = ContainsObject::create();
        $result = $this->mapper->map($object, ContainsArray::class);
        $this->initialize($result);

        $dataCollector = $this->get('test.rekalogika.mapper.data_collector');
        $this->assertInstanceOf(MapperDataCollector::class, $dataCollector);

        $mappings = $dataCollector->getMappings();

        $firstMapping = $mappings[0] ?? null;
        $secondMapping = $mappings[1] ?? null;

        $this->assertNull($secondMapping);
        $this->assertNotNull($firstMapping);

        $this->assertNull($firstMapping->getPath());
        $this->assertEquals(ObjectToObjectTransformer::class, $firstMapping->getTransformerClass());

        $subFirstMapping = $firstMapping->getNestedTraceData()[0] ?? null;

        $this->assertNotNull($subFirstMapping);
        $this->assertEquals('data', $subFirstMapping->getPath());
        $this->assertEquals(ArrayObjectTransformer::class, $subFirstMapping->getTransformerClass());
    }
}
