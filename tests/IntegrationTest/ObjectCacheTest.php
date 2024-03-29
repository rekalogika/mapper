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

use Rekalogika\Mapper\ObjectCache\Exception\CircularReferenceException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeFactory;

class ObjectCacheTest extends FrameworkTestCase
{
    private function createObjectCache(): ObjectCache
    {
        $typeResolver = $this->get('rekalogika.mapper.type_resolver');
        $this->assertInstanceOf(TypeResolverInterface::class, $typeResolver);

        return new ObjectCache($typeResolver);
    }

    public function testPreCache(): void
    {
        $objectCache = $this->createObjectCache();

        $source = new ObjectWithScalarProperties();
        $targetType = TypeFactory::objectOfClass(ObjectWithScalarProperties::class);
        $target = new ObjectWithScalarPropertiesDto();

        $objectCache->preCache($source, $targetType);

        $this->expectException(CircularReferenceException::class);
        $objectCache->getTarget($source, $targetType);
    }

    public function testSaveGetTargetWithoutPreCache(): void
    {
        $objectCache = $this->createObjectCache();

        $source = new ObjectWithScalarProperties();
        $targetType = TypeFactory::objectOfClass(ObjectWithScalarProperties::class);
        $target = new ObjectWithScalarPropertiesDto();

        $objectCache->saveTarget($source, $targetType, $target);

        $this->assertSame($target, $objectCache->getTarget($source, $targetType));
    }

    public function testSaveGetTargetWithPreCache(): void
    {
        $objectCache = $this->createObjectCache();

        $source = new ObjectWithScalarProperties();
        $targetType = TypeFactory::objectOfClass(ObjectWithScalarProperties::class);
        $target = new ObjectWithScalarPropertiesDto();

        $objectCache->preCache($source, $targetType);
        $objectCache->saveTarget($source, $targetType, $target);

        $this->assertSame($target, $objectCache->getTarget($source, $targetType));
    }
}
