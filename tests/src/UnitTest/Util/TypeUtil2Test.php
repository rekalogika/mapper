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
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\TypeInfo\Type;

class TypeUtil2Test extends TestCase
{
    /**
     * @param array<int,string> $expected
     */
    #[DataProvider('getSimpleTypesProvider')]
    public function testGetAllTypeStrings(
        Type $type,
        array $expected,
        bool $withParents = false,
    ): void {
        $result = TypeUtil::getAllTypeStrings($type, $withParents);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return iterable<array-key,array{0:Type,1:array<int,string>,2?:bool}>
     */
    public static function getSimpleTypesProvider(): iterable
    {
        yield [
            TypeFactory::null(),
            [
                'null',
            ],
        ];

        yield [
            TypeFactory::bool(),
            [
                'bool',
            ],
        ];

        yield [
            TypeFactory::int(),
            [
                'int',
            ],
        ];

        yield [
            TypeFactory::float(),
            [
                'float',
            ],
        ];

        yield [
            TypeFactory::string(),
            [
                'string',
            ],
        ];

        yield [
            TypeFactory::array(),
            [
                'array',
            ],
        ];

        yield [
            TypeFactory::objectOfClass(\DateTime::class),
            [
                'DateTime',
            ],
        ];

        yield [
            TypeFactory::resource(),
            [
                'resource',
            ],
        ];

        yield [
            TypeFactory::arrayWithKeyValue(
                TypeFactory::string(),
                TypeFactory::int(),
            ),
            [
                'array<string,int>',
            ],
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                TypeFactory::string(),
                TypeFactory::int(),
            ),
            [
                'Traversable<string,int>',
            ],
        ];

        yield [
            TypeFactory::iterable(),
            [
                'array',
                'Traversable',
            ],
        ];

        yield [
            Type::iterable(TypeFactory::int(), TypeFactory::string()),
            [
                'array<string,int>',
                'Traversable<string,int>',
            ],
        ];

        yield [
            Type::iterable(
                Type::union(
                    TypeFactory::int(),
                    TypeFactory::objectOfClass(\DateTime::class),
                ),
                Type::union(TypeFactory::string(), TypeFactory::int()),
            ),
            [
                'array<int,DateTime>',
                'Traversable<int,DateTime>',
                'array<int,int>',
                'Traversable<int,int>',
                'array<string,DateTime>',
                'Traversable<string,DateTime>',
                'array<string,int>',
                'Traversable<string,int>',
            ],
        ];

        yield [
            Type::nullable(Type::iterable(
                Type::union(
                    TypeFactory::int(),
                    TypeFactory::objectOfClass(\DateTime::class),
                ),
                Type::union(TypeFactory::string(), TypeFactory::int()),
            )),
            [
                'array<int,DateTime>',
                'Traversable<int,DateTime>',
                'array<int,int>',
                'Traversable<int,int>',
                'array<string,DateTime>',
                'Traversable<string,DateTime>',
                'array<string,int>',
                'Traversable<string,int>',
                'null',
            ],
        ];

        yield [
            Type::nullable(TypeFactory::objectWithKeyValue(
                \Traversable::class,
                Type::union(TypeFactory::string(), TypeFactory::int()),
                Type::union(
                    TypeFactory::int(),
                    TypeFactory::objectOfClass(\DateTime::class),
                ),
            )),
            [
                'Traversable<int,DateTime>',
                'Traversable<int,int>',
                'Traversable<string,DateTime>',
                'Traversable<string,int>',
                'null',
            ],
        ];

        yield [
            Type::nullable(TypeFactory::objectWithKeyValue(
                \Traversable::class,
                TypeFactory::int(),
                TypeFactory::objectWithKeyValue(
                    \Traversable::class,
                    Type::union(TypeFactory::string(), TypeFactory::int()),
                    Type::union(
                        TypeFactory::int(),
                        TypeFactory::objectOfClass(\DateTime::class),
                    ),
                ),
            )),
            [
                'Traversable<int,Traversable<int,DateTime>>',
                'Traversable<int,Traversable<int,int>>',
                'Traversable<int,Traversable<string,DateTime>>',
                'Traversable<int,Traversable<string,int>>',
                'null',
            ],
        ];

        yield [
            Type::nullable(TypeFactory::objectWithKeyValue(
                \ArrayObject::class,
                TypeFactory::int(),
                TypeFactory::objectWithKeyValue(
                    \ArrayObject::class,
                    Type::union(TypeFactory::string(), TypeFactory::int()),
                    Type::union(
                        TypeFactory::int(),
                        TypeFactory::objectOfClass(\DateTime::class),
                    ),
                ),
            )),
            [
                'ArrayObject<int,ArrayObject<int,DateTime>>',
                'ArrayObject<int,ArrayObject<int,int>>',
                'ArrayObject<int,ArrayObject<string,DateTime>>',
                'ArrayObject<int,ArrayObject<string,int>>',
                'null',
            ],
        ];

        yield [
            Type::nullable(TypeFactory::objectWithKeyValue(
                \IteratorAggregate::class,
                TypeFactory::int(),
                TypeFactory::string(),
            )),
            [
                'IteratorAggregate<int,string>',
                'IteratorAggregate<int,mixed>',
                'IteratorAggregate<mixed,string>',
                'IteratorAggregate<mixed,mixed>',
                'IteratorAggregate',
                'Traversable<int,string>',
                'Traversable<int,mixed>',
                'Traversable<mixed,string>',
                'Traversable<mixed,mixed>',
                'Traversable',
                'object',
                'null',
                'mixed',
            ],
            true,
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                TypeFactory::int(),
                TypeFactory::objectWithKeyValue(
                    \IteratorAggregate::class,
                    TypeFactory::int(),
                    TypeFactory::string(),
                ),
            ),
            [
                "Traversable<int,IteratorAggregate<int,string>>",
                "Traversable<int,IteratorAggregate<int,mixed>>",
                "Traversable<int,IteratorAggregate<mixed,string>>",
                "Traversable<int,IteratorAggregate<mixed,mixed>>",
                "Traversable<int,IteratorAggregate>",
                "Traversable<int,Traversable<int,string>>",
                "Traversable<int,Traversable<int,mixed>>",
                "Traversable<int,Traversable<mixed,string>>",
                "Traversable<int,Traversable<mixed,mixed>>",
                "Traversable<int,Traversable>",
                "Traversable<int,object>",
                "Traversable<int,mixed>",
                "Traversable<mixed,IteratorAggregate<int,string>>",
                "Traversable<mixed,IteratorAggregate<int,mixed>>",
                "Traversable<mixed,IteratorAggregate<mixed,string>>",
                "Traversable<mixed,IteratorAggregate<mixed,mixed>>",
                "Traversable<mixed,IteratorAggregate>",
                "Traversable<mixed,Traversable<int,string>>",
                "Traversable<mixed,Traversable<int,mixed>>",
                "Traversable<mixed,Traversable<mixed,string>>",
                "Traversable<mixed,Traversable<mixed,mixed>>",
                "Traversable<mixed,Traversable>",
                "Traversable<mixed,object>",
                "Traversable<mixed,mixed>",
                "Traversable",
                "object",
                "mixed",
            ],
            true,
        ];

        yield [
            TypeFactory::objectWithKeyValue(
                \Traversable::class,
                TypeFactory::int(),
                Type::nullable(TypeFactory::objectWithKeyValue(
                    \IteratorAggregate::class,
                    TypeFactory::int(),
                    TypeFactory::string(),
                )),
            ),
            [
                "Traversable<int,IteratorAggregate<int,string>>",
                "Traversable<int,IteratorAggregate<int,mixed>>",
                "Traversable<int,IteratorAggregate<mixed,string>>",
                "Traversable<int,IteratorAggregate<mixed,mixed>>",
                "Traversable<int,IteratorAggregate>",
                "Traversable<int,Traversable<int,string>>",
                "Traversable<int,Traversable<int,mixed>>",
                "Traversable<int,Traversable<mixed,string>>",
                "Traversable<int,Traversable<mixed,mixed>>",
                "Traversable<int,Traversable>",
                "Traversable<int,object>",
                "Traversable<int,null>",
                "Traversable<int,mixed>",
                "Traversable<mixed,IteratorAggregate<int,string>>",
                "Traversable<mixed,IteratorAggregate<int,mixed>>",
                "Traversable<mixed,IteratorAggregate<mixed,string>>",
                "Traversable<mixed,IteratorAggregate<mixed,mixed>>",
                "Traversable<mixed,IteratorAggregate>",
                "Traversable<mixed,Traversable<int,string>>",
                "Traversable<mixed,Traversable<int,mixed>>",
                "Traversable<mixed,Traversable<mixed,string>>",
                "Traversable<mixed,Traversable<mixed,mixed>>",
                "Traversable<mixed,Traversable>",
                "Traversable<mixed,object>",
                "Traversable<mixed,null>",
                "Traversable<mixed,mixed>",
                "Traversable",
                "object",
                "mixed",
            ],
            true,
        ];

    }
}
