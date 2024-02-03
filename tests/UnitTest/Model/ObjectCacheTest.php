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

namespace Rekalogika\Mapper\Tests\UnitTest\Model;

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\ObjectCache\Exception\CachedTargetObjectNotFoundException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\TypeResolver\Implementation\TypeResolver;
use Rekalogika\Mapper\Util\TypeFactory;

class ObjectCacheTest extends TestCase
{
    public function testObjectCache(): void
    {
        $typeResolver = new TypeResolver();
        $objectCache = new ObjectCache($typeResolver);
        $source = new \stdClass();
        $context = Context::create();

        $this->assertFalse($objectCache->containsTarget($source, TypeFactory::int()));

        $target = new \stdClass();
        $objectCache->saveTarget($source, TypeFactory::int(), $target);

        $this->assertTrue($objectCache->containsTarget($source, TypeFactory::int()));
        $this->assertSame($target, $objectCache->getTarget($source, TypeFactory::int()));

        $this->expectException(CachedTargetObjectNotFoundException::class);
        $objectCache->getTarget($source, TypeFactory::float());
    }
}
