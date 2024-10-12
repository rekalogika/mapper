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
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\ObjectImplementingStringable;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeBackedEnum;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeEnum;
use Rekalogika\Mapper\Transformer\Implementation\CopyTransformer;
use Rekalogika\Mapper\Transformer\Implementation\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\Implementation\PresetTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\Implementation\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\PropertyInfo\Type;

class MappingTest extends FrameworkTestCase
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
        string $transformerClass,
    ): void {
        $transformerRegistry = $this->get('rekalogika.mapper.transformer_registry');
        $this->assertInstanceOf(
            TransformerRegistryInterface::class,
            $transformerRegistry,
        );

        $searchResult = $transformerRegistry->findBySourceAndTargetTypes(
            sourceTypes: $sources,
            targetTypes: $targets,
        );

        $this->assertNotEmpty($searchResult);

        $searchResultArray = iterator_to_array($searchResult);

        $selected = $searchResultArray[0] ?? null;

        $this->assertNotNull($selected);

        if (str_contains($selected->getTransformerServiceId(), PresetTransformer::class)) {
            $selected = $searchResultArray[1] ?? null;
        }

        $this->assertNotNull($selected);

        $transformer = $transformerRegistry->get(
            $selected->getTransformerServiceId(),
        );

        $this->assertTransformerInstanceOf(
            $transformerClass,
            $transformer,
        );
    }

    /**
     * @return iterable<array-key,array<array-key,mixed>>
     */
    public static function mappingTestProvider(): iterable
    {
        //
        // scalar
        //

        yield [
            [
                TypeFactory::int(),
            ],
            [
                TypeFactory::int(),
            ],
            ScalarToScalarTransformer::class,
        ];

        yield [
            [
                TypeFactory::int(),
            ],
            [
                TypeFactory::float(),
            ],
            ScalarToScalarTransformer::class,
        ];

        yield [
            [
                TypeFactory::int(),
            ],
            [
                TypeFactory::string(),
            ],
            ScalarToScalarTransformer::class,
        ];

        yield [
            [
                TypeFactory::int(),
            ],
            [
                TypeFactory::bool(),
            ],
            ScalarToScalarTransformer::class,
        ];

        yield [
            [
                TypeFactory::int(),
            ],
            [
                TypeFactory::resource(),
            ],
            CopyTransformer::class,
        ];

        //
        // datetime
        //

        yield [
            [
                TypeFactory::objectOfClass(\DateTimeInterface::class),
            ],
            [
                TypeFactory::objectOfClass(\DateTimeImmutable::class),
            ],
            DateTimeTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\DateTimeInterface::class),
            ],
            [
                TypeFactory::objectOfClass(\DateTime::class),
            ],
            DateTimeTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\DateTimeInterface::class),
            ],
            [
                TypeFactory::objectOfClass(DatePoint::class),
            ],
            DateTimeTransformer::class,
        ];

        //
        // stringable
        //

        yield [
            [
                TypeFactory::objectOfClass(ObjectImplementingStringable::class),
            ],
            [
                TypeFactory::string(),
            ],
            ObjectToStringTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(SomeBackedEnum::class),
            ],
            [
                TypeFactory::string(),
            ],
            ObjectToStringTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(SomeEnum::class),
            ],
            [
                TypeFactory::string(),
            ],
            ObjectToStringTransformer::class,
        ];

        //
        // string to enum
        //

        yield [
            [
                TypeFactory::string(),
            ],
            [
                TypeFactory::objectOfClass(SomeBackedEnum::class),
            ],
            StringToBackedEnumTransformer::class,
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
                TypeFactory::objectOfClass(\ArrayAccess::class),
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\ArrayObject::class),
            ],
            [
                TypeFactory::objectOfClass(\ArrayAccess::class),
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\ArrayObject::class),
            ],
            [
                TypeFactory::objectOfClass(\ArrayObject::class),
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(\ArrayObject::class),
            ],
            [
                TypeFactory::objectOfClass(ArrayCollection::class),
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(ArrayCollection::class),
            ],
            [
                TypeFactory::objectOfClass(Collection::class),
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(Collection::class),
            ],
            [
                TypeFactory::objectOfClass(\ArrayAccess::class),
            ],
            TraversableToArrayAccessTransformer::class,
        ];

        yield [
            [
                TypeFactory::objectOfClass(Collection::class),
            ],
            [
                TypeFactory::objectOfClass(\ArrayObject::class),
            ],
            TraversableToArrayAccessTransformer::class,
        ];
    }
}
