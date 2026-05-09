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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;

class TypeUtilTest extends TestCase
{
    #[DataProvider('typeGuessProvider')]
    public function testTypeGuess(
        mixed $object,
        string $builtInType,
        ?string $className = null,
    ): void {
        $type = TypeGuesser::guessTypeFromVariable($object);

        if ($type instanceof ObjectType) {
            $this->assertSame('object', $builtInType);
            $this->assertSame($className, $type->getClassName());

            return;
        }

        if ($type instanceof CollectionType) {
            $wrapped = $type->getWrappedType();
            $this->assertInstanceOf(BuiltinType::class, $wrapped);
            $this->assertSame($builtInType, $wrapped->getTypeIdentifier()->value);
            $this->assertNull($className);

            return;
        }

        $this->assertInstanceOf(BuiltinType::class, $type);
        $this->assertSame($builtInType, $type->getTypeIdentifier()->value);
        $this->assertNull($className);
    }

    /**
     * @return iterable<array-key,array{0:mixed,1:string,2?:class-string}>
     */
    public static function typeGuessProvider(): iterable
    {
        yield [null, 'null'];
        yield [true, 'bool'];
        yield [false, 'bool'];
        yield [1, 'int'];
        yield [1.1, 'float'];
        yield ['string', 'string'];
        yield [new \ArrayObject(), 'object', \ArrayObject::class];
        yield [[], 'array'];
        yield [fopen('php://memory', 'r'), 'resource'];
    }

    #[DataProvider('isSimpleTypeProvider')]
    public function testIsSimpleType(Type $type, bool $isSimple): void
    {
        $this->assertSame($isSimple, TypeUtil::isSimpleType($type));
    }

    /**
     * @return iterable<array-key,array{0:Type,1:bool}>
     */
    public static function isSimpleTypeProvider(): iterable
    {
        yield [TypeFactory::null(), true];
        yield [TypeFactory::bool(), true];
        yield [TypeFactory::int(), true];
        yield [TypeFactory::float(), true];
        yield [TypeFactory::string(), true];
        yield [TypeFactory::array(), true];
        yield [TypeFactory::objectOfClass(\DateTime::class), true];
        yield [TypeFactory::resource(), true];

        yield [
            TypeFactory::arrayWithKeyValue(
                TypeFactory::string(),
                TypeFactory::int(),
            ),
            true,
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                TypeFactory::string(),
                TypeFactory::int(),
            ),
            true,
        ];

        yield [
            Type::iterable(TypeFactory::int(), TypeFactory::string()),
            false,
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                TypeFactory::string(),
                TypeFactory::int(),
            ),
            true,
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                Type::union(TypeFactory::string(), TypeFactory::int()),
                TypeFactory::int(),
            ),
            false,
        ];

        yield [
            Type::nullable(TypeFactory::objectOfClass(\Traversable::class)),
            false,
        ];
    }

    /** @psalm-suppress DeprecatedClass */
    #[DataProvider('getTypeStringProvider')]
    public function testGetTypeString(Type $type, string $expected): void
    {
        $this->assertSame($expected, TypeUtil::getTypeString($type));
    }

    /**
     * @return iterable<array-key,array{0:Type,1:string}>
     */
    public static function getTypeStringProvider(): iterable
    {
        yield [TypeFactory::null(), 'null'];
        yield [TypeFactory::bool(), 'bool'];
        yield [TypeFactory::int(), 'int'];
        yield [TypeFactory::float(), 'float'];
        yield [TypeFactory::string(), 'string'];
        yield [TypeFactory::array(), 'array'];
        yield [TypeFactory::resource(), 'resource'];
        yield [TypeFactory::callable(), 'callable'];
        yield [TypeFactory::true(), 'true'];
        yield [TypeFactory::false(), 'false'];

        yield [
            TypeFactory::objectOfClass(\Traversable::class),
            \Traversable::class,
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                TypeFactory::string(),
                TypeFactory::int(),
            ),
            \Traversable::class . '<string,int>',
        ];

        yield [
            TypeFactory::arrayWithKeyValue(
                TypeFactory::string(),
                TypeFactory::int(),
            ),
            'array<string,int>',
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                TypeFactory::string(),
                TypeFactory::objectWithKeyValue(
                    \Traversable::class,
                    TypeFactory::string(),
                    TypeFactory::int(),
                ),
            ),
            'Traversable<string,Traversable<string,int>>',
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                null,
                TypeFactory::objectWithKeyValue(
                    \Traversable::class,
                    TypeFactory::string(),
                    TypeFactory::int(),
                ),
            ),
            'Traversable<mixed,Traversable<string,int>>',
        ];
    }
}
