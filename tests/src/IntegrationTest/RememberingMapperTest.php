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

use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\RememberingMapper\RememberingMapper;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesWithNullContents;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithFloatPropertiesDto;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithIntPropertiesDto;

class RememberingMapperTest extends FrameworkTestCase
{
    public function testMapping(): void
    {
        $mapper = $this->get(RememberingMapper::class);

        $source1 = new ObjectWithScalarProperties();
        $target1 = ObjectWithFloatPropertiesDto::class;
        $result1 = $mapper->map($source1, $target1);

        $source2 = new ObjectWithScalarPropertiesWithNullContents();
        $target2 = ObjectWithIntPropertiesDto::class;
        $result2 = $mapper->map($source2, $target2);

        $shouldbeSource1 = $mapper->map($result1, $source1::class);
        $shouldbeSource2 = $mapper->map($result2, $source2::class);

        $this->assertEquals($source1, $shouldbeSource1);
        $this->assertEquals($source2, $shouldbeSource2);
    }
}
