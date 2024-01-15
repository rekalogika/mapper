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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Rekalogika\Mapper\Attribute\InheritanceMap;
use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\ObjectImplementingStringable;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeBackedEnum;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeEnum;
use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Transformer\CopyTransformer;
use Rekalogika\Mapper\Transformer\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\InheritanceMapTransformer;
use Rekalogika\Mapper\Transformer\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\PropertyInfo\Type;

class MappingTest extends AbstractIntegrationTest
{
    /**
     * Testing mapping against default mapping table
     * @dataProvider mappingTestProvider
     * @param array<int,Type|MixedType> $sources
     * @param array<int,Type|MixedType> $targets
     * @param class-string $transformerClass
     */
    public function testMapping(
        array $sources,
        array $targets,
        string $transformerClass
    ): void {
        $searchResult = $this->transformerRegistry->findBySourceAndTargetTypes(
            sourceTypes: $sources,
            targetTypes: $targets,
        );

        $this->assertNotEmpty($searchResult);

        $this->assertInstanceOf(
            $transformerClass,
            $searchResult[0]?->getTransformer()
        );
    }

    /**
     * @return iterable<array-key,array<array-key,mixed>>
     */
    public function mappingTestProvider(): iterable
    {
        //
        // scalar
        //

        yield [
            [
                TypeFactory::int()
            ],
            [
                TypeFactory::int()
            ],
            ScalarToScalarTransformer::class,
        ];

        yield [
            [
                TypeFactory::int()
            ],
            [
                TypeFactory::float()
            ],
            ScalarToScalarTransformer::class,
        ];

        yield [
            [
                TypeFactory::int()
            ],
            [
                TypeFactory::string()
            ],
            ScalarToScalarTransformer::class,
        ];

        yield [
            [
                TypeFactory::int()
            ],
            [
                TypeFactory::bool()
            ],
            ScalarToScalarTransformer::class,
        ];

        yield [
            [
                TypeFactory::int()
            ],
            [
                TypeFactory::resource()
            ],
            CopyTransformer::class,
        ];

        //
        // datetime
        //

        yield [
            [
                TypeFactory::objectOfClass(\DateTimeInterface::class)
            ],
            [
                TypeFactory::objectOfClass(\DateTimeImmutable::class)
            ],
            DateTimeTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\DateTimeInterface::class)
            ],
            [
                TypeFactory::objectOfClass(\DateTime::class)
            ],
            DateTimeTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\DateTimeInterface::class)
            ],
            [
                TypeFactory::objectOfClass(DatePoint::class)
            ],
            DateTimeTransformer::class,
        ];

        //
        // stringable
        //

        yield [
            [
                TypeFactory::objectOfClass(ObjectImplementingStringable::class)
            ],
            [
                TypeFactory::string()
            ],
            ObjectToStringTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(SomeBackedEnum::class)
            ],
            [
                TypeFactory::string()
            ],
            ObjectToStringTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(SomeEnum::class)
            ],
            [
                TypeFactory::string()
            ],
            ObjectToStringTransformer::class,
        ];

        //
        // string to enum
        //

        yield [
            [
                TypeFactory::string()
            ],
            [
                TypeFactory::objectOfClass(SomeBackedEnum::class)
            ],
            StringToBackedEnumTransformer::class,
        ];

        //
        // inheritance
        //

        yield [
            [
                TypeFactory::object(),
            ],
            [
                TypeFactory::objectOfClass(InheritanceMap::class)
            ],
            InheritanceMapTransformer::class,
        ];

        //
        // traversable to array access
        //

        yield [
            [
                TypeFactory::array(),
            ],
            [
                TypeFactory::array(),
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::array(),
            ],
            [
                TypeFactory::objectOfClass(\ArrayAccess::class)
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\ArrayObject::class),
            ],
            [
                TypeFactory::objectOfClass(\ArrayAccess::class)
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\ArrayObject::class),
            ],
            [
                TypeFactory::objectOfClass(\ArrayObject::class)
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\ArrayObject::class),
            ],
            [
                TypeFactory::objectOfClass(ArrayCollection::class)
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(ArrayCollection::class),
            ],
            [
                TypeFactory::objectOfClass(Collection::class)
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(Collection::class),
            ],
            [
                TypeFactory::objectOfClass(\ArrayAccess::class)
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(Collection::class),
            ],
            [
                TypeFactory::objectOfClass(\ArrayObject::class)
            ],
            TraversableToArrayAccessTransformer::class,
        ];
    }
}
