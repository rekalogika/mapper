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
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;

class TypeFactoryTest extends TestCase
{
    public function testTypes(): void
    {
        $this->assertBuiltin(TypeIdentifier::NULL, TypeFactory::null());
        $this->assertBuiltin(TypeIdentifier::STRING, TypeFactory::string());
        $this->assertBuiltin(TypeIdentifier::INT, TypeFactory::int());
        $this->assertBuiltin(TypeIdentifier::FLOAT, TypeFactory::float());
        $this->assertBuiltin(TypeIdentifier::BOOL, TypeFactory::bool());
        $this->assertBuiltin(TypeIdentifier::RESOURCE, TypeFactory::resource());
        $this->assertBuiltin(TypeIdentifier::CALLABLE, TypeFactory::callable());
        $this->assertBuiltin(TypeIdentifier::TRUE, TypeFactory::true());
        $this->assertBuiltin(TypeIdentifier::FALSE, TypeFactory::false());

        $array = TypeFactory::array();
        $this->assertInstanceOf(CollectionType::class, $array);
        $arrayWrapped = $array->getWrappedType();
        $this->assertInstanceOf(BuiltinType::class, $arrayWrapped);
        $this->assertSame(TypeIdentifier::ARRAY, $arrayWrapped->getTypeIdentifier());

        $object = TypeFactory::objectOfClass(\DateTime::class);
        $this->assertInstanceOf(ObjectType::class, $object);
        $this->assertSame(\DateTime::class, $object->getClassName());

        $arrayWithKeyValue = TypeFactory::arrayWithKeyValue(
            TypeFactory::string(),
            TypeFactory::int(),
        );
        $this->assertInstanceOf(CollectionType::class, $arrayWithKeyValue);
        $this->assertBuiltin(
            TypeIdentifier::STRING,
            $arrayWithKeyValue->getCollectionKeyType(),
        );
        $this->assertBuiltin(
            TypeIdentifier::INT,
            $arrayWithKeyValue->getCollectionValueType(),
        );

        $objectWithKeyValue = TypeFactory::objectWithKeyValue(
            \Traversable::class,
            TypeFactory::string(),
            TypeFactory::int(),
        );
        $this->assertInstanceOf(CollectionType::class, $objectWithKeyValue);
        $this->assertBuiltin(
            TypeIdentifier::STRING,
            $objectWithKeyValue->getCollectionKeyType(),
        );
        $this->assertBuiltin(
            TypeIdentifier::INT,
            $objectWithKeyValue->getCollectionValueType(),
        );
    }

    private function assertBuiltin(TypeIdentifier $expected, mixed $actual): void
    {
        $this->assertInstanceOf(BuiltinType::class, $actual);
        $this->assertSame($expected, $actual->getTypeIdentifier());
    }
}
