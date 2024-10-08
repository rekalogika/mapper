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
use Rekalogika\Mapper\Tests\Fixtures\Attribute\ObjectWithAttribute;
use Rekalogika\Mapper\Tests\Fixtures\Attribute\SomeAttribute;
use Rekalogika\Mapper\Tests\Fixtures\Attribute\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\Attribute\SomeObjectWithInvalidPropertyAttribute;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeFactory;

class AttributeTest extends FrameworkTestCase
{
    public function testAttribute(): void
    {
        $class = ObjectWithAttribute::class;
        $type = TypeFactory::objectOfClass($class);

        $typeResolver = $this->get('rekalogika.mapper.type_resolver');
        $this->assertInstanceOf(TypeResolverInterface::class, $typeResolver);

        $typeStrings = $typeResolver->getAcceptedTransformerInputTypeStrings($type);

        $this->assertContainsEquals(SomeAttribute::class, $typeStrings);
    }

    public function testMappingWithInvalidPropertyAttribute(): void
    {
        $source = new SomeObject();
        $target = $this->mapper->map($source, SomeObjectWithInvalidPropertyAttribute::class);

        $this->assertEquals($source->foo, $target->foo);
    }
}
