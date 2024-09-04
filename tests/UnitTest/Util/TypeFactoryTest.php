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

namespace Rekalogika\Mapper\Tests\UnitTest\Util;

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Util\TypeFactory;

class TypeFactoryTest extends TestCase
{
    public function testTypes(): void
    {
        $this->assertSame('null', TypeFactory::null()->getBuiltinType());
        $this->assertSame('string', TypeFactory::string()->getBuiltinType());
        $this->assertSame('int', TypeFactory::int()->getBuiltinType());
        $this->assertSame('float', TypeFactory::float()->getBuiltinType());
        $this->assertSame('bool', TypeFactory::bool()->getBuiltinType());
        $this->assertSame('array', TypeFactory::array()->getBuiltinType());
        $this->assertSame('resource', TypeFactory::resource()->getBuiltinType());
        $this->assertSame('callable', TypeFactory::callable()->getBuiltinType());
        $this->assertSame('true', TypeFactory::true()->getBuiltinType());
        $this->assertSame('false', TypeFactory::false()->getBuiltinType());

        $object = TypeFactory::objectOfClass(\DateTime::class);
        $this->assertSame('object', $object->getBuiltinType());
        $this->assertSame(\DateTime::class, $object->getClassName());

        $arrayWithKeyValue = TypeFactory::arrayWithKeyValue(
            TypeFactory::string(),
            TypeFactory::int()
        );
        $this->assertSame('array', $arrayWithKeyValue->getBuiltinType());
        $this->assertSame('string', $arrayWithKeyValue->getCollectionKeyTypes()[0]->getBuiltinType());
        $this->assertSame('int', $arrayWithKeyValue->getCollectionValueTypes()[0]->getBuiltinType());

        $objectWithKeyValue = TypeFactory::objectWithKeyValue(
            \Traversable::class,
            TypeFactory::string(),
            TypeFactory::int()
        );
        $this->assertSame('object', $objectWithKeyValue->getBuiltinType());
        $this->assertSame(\Traversable::class, $objectWithKeyValue->getClassName());
        $this->assertSame('string', $objectWithKeyValue->getCollectionKeyTypes()[0]->getBuiltinType());
        $this->assertSame('int', $objectWithKeyValue->getCollectionValueTypes()[0]->getBuiltinType());
    }
}
