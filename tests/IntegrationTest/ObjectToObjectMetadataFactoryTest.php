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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Tests\Common\AbstractFrameworkTest;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;

class ObjectToObjectMetadataFactoryTest extends AbstractFrameworkTest
{
    public function testObjectToObjectMetadataFactory(): void
    {
        $factory = $this->get('rekalogika.mapper.object_to_object_metadata_factory');
        $this->assertInstanceOf(ObjectToObjectMetadataFactoryInterface::class, $factory);

        $metadata = $factory->createObjectToObjectMetadata(
            ObjectWithScalarProperties::class,
            ObjectWithScalarPropertiesDto::class,
            Context::create()
        );

        $this->assertEquals(ObjectWithScalarProperties::class, $metadata->getSourceClass());
        $this->assertEquals(ObjectWithScalarPropertiesDto::class, $metadata->getTargetClass());
        $this->assertCount(4, $metadata->getPropertyMappings());
    }
}
