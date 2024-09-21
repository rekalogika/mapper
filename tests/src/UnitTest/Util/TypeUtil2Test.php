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
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class TypeUtil2Test extends TestCase
{
    /**
     * @dataProvider getSimpleTypesProvider
     * @param array<int,string> $expected
     */
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
            new Type(
                builtinType: 'iterable',
            ),
            [
                'array',
                'Traversable',
            ],
        ];

        yield [
            new Type(
                builtinType: 'iterable',
                collection: true,
                collectionKeyType: [
                    TypeFactory::string(),
                ],
                collectionValueType: [
                    TypeFactory::int(),
                ],
            ),
            [
                'array<string,int>',
                'Traversable<string,int>',
            ],
        ];

        yield [
            new Type(
                builtinType: 'iterable',
                collection: true,
                collectionKeyType: [
                    TypeFactory::string(),
                    TypeFactory::int(),
                ],
                collectionValueType: [
                    TypeFactory::int(),
                    TypeFactory::objectOfClass(\DateTime::class),
                ],
            ),
            [
                "array<string,int>",
                "Traversable<string,int>",
                "array<string,DateTime>",
                "Traversable<string,DateTime>",
                "array<int,int>",
                "Traversable<int,int>",
                "array<int,DateTime>",
                "Traversable<int,DateTime>",
            ],
        ];

        yield [
            new Type(
                builtinType: 'iterable',
                nullable: true,
                collection: true,
                collectionKeyType: [
                    TypeFactory::string(),
                    TypeFactory::int(),
                ],
                collectionValueType: [
                    TypeFactory::int(),
                    TypeFactory::objectOfClass(\DateTime::class),
                ],
            ),
            [
                'array<string,int>',
                'Traversable<string,int>',
                'array<string,DateTime>',
                'Traversable<string,DateTime>',
                'array<int,int>',
                'Traversable<int,int>',
                'array<int,DateTime>',
                'Traversable<int,DateTime>',
                'null',
            ],
        ];

        yield [
            new Type(
                builtinType: 'object',
                class: \Traversable::class,
                nullable: true,
                collection: true,
                collectionKeyType: [
                    TypeFactory::string(),
                    TypeFactory::int(),
                ],
                collectionValueType: [
                    TypeFactory::int(),
                    TypeFactory::objectOfClass(\DateTime::class),
                ],
            ),
            [
                'Traversable<string,int>',
                'Traversable<string,DateTime>',
                'Traversable<int,int>',
                'Traversable<int,DateTime>',
                'null',
            ],
        ];

        yield [
            new Type(
                builtinType: 'object',
                class: \Traversable::class,
                nullable: true,
                collection: true,
                collectionKeyType: [
                    TypeFactory::int(),
                ],
                collectionValueType: [
                    new Type(
                        builtinType: 'object',
                        class: \Traversable::class,
                        collection: true,
                        collectionKeyType: [
                            TypeFactory::string(),
                            TypeFactory::int(),
                        ],
                        collectionValueType: [
                            TypeFactory::int(),
                            TypeFactory::objectOfClass(\DateTime::class),
                        ],
                    ),
                ],
            ),
            [
                "Traversable<int,Traversable<string,int>>",
                "Traversable<int,Traversable<string,DateTime>>",
                "Traversable<int,Traversable<int,int>>",
                "Traversable<int,Traversable<int,DateTime>>",
                "null",
            ],
        ];

        yield [
            new Type(
                builtinType: 'object',
                class: \ArrayObject::class,
                nullable: true,
                collection: true,
                collectionKeyType: [
                    TypeFactory::int(),
                ],
                collectionValueType: [
                    new Type(
                        builtinType: 'object',
                        class: \ArrayObject::class,
                        collection: true,
                        collectionKeyType: [
                            TypeFactory::string(),
                            TypeFactory::int(),
                        ],
                        collectionValueType: [
                            TypeFactory::int(),
                            TypeFactory::objectOfClass(\DateTime::class),
                        ],
                    ),
                ],
            ),
            [
                "ArrayObject<int,ArrayObject<string,int>>",
                "ArrayObject<int,ArrayObject<string,DateTime>>",
                "ArrayObject<int,ArrayObject<int,int>>",
                "ArrayObject<int,ArrayObject<int,DateTime>>",
                "null",
            ],
        ];

        yield [
            new Type(
                builtinType: 'object',
                class: \IteratorAggregate::class,
                nullable: true,
                collection: true,
                collectionKeyType: [
                    TypeFactory::int(),
                ],
                collectionValueType: [
                    TypeFactory::string(),
                ],
            ),
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
            new Type(
                builtinType: 'object',
                class: \Traversable::class,
                collection: true,
                collectionKeyType: [
                    TypeFactory::int(),
                ],
                collectionValueType: [
                    new Type(
                        builtinType: 'object',
                        class: \IteratorAggregate::class,
                        collection: true,
                        collectionKeyType: [
                            TypeFactory::int(),
                        ],
                        collectionValueType: [
                            TypeFactory::string(),
                        ],
                    ),
                ],
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
            new Type(
                builtinType: 'object',
                class: \Traversable::class,
                collection: true,
                collectionKeyType: [
                    TypeFactory::int(),
                ],
                collectionValueType: [
                    new Type(
                        builtinType: 'object',
                        class: \IteratorAggregate::class,
                        nullable: true,
                        collection: true,
                        collectionKeyType: [
                            TypeFactory::int(),
                        ],
                        collectionValueType: [
                            TypeFactory::string(),
                        ],
                    ),
                ],
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
