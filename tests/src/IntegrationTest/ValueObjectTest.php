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
use Rekalogika\Mapper\Tests\Fixtures\ValueObject\PublicGetter;
use Rekalogika\Mapper\Tests\Fixtures\ValueObject\PublicPropertyPublicGetter;
use Rekalogika\Mapper\Tests\Fixtures\ValueObject\PublicSetter;
use Rekalogika\Mapper\Tests\Fixtures\ValueObject\ReadonlyPublicProperty;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ObjectWithImmutableSetter;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\AttributesExtractor;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\ClassMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\DynamicPropertiesDeterminer;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\PropertyAccessInfoExtractor;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\ValueObjectDeterminer;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

class ValueObjectTest extends FrameworkTestCase
{
    /**
     * @return iterable<array-key,array{class-string,bool}>
     */
    public static function provideValueObject(): iterable
    {
        yield self::desc(ObjectWithImmutableSetter::class) => [
            ObjectWithImmutableSetter::class,
            false,
        ];

        yield self::desc(DatePoint::class) => [
            DatePoint::class,
            true,
        ];

        yield self::desc(PublicGetter::class) => [
            PublicGetter::class,
            true,
        ];

        yield self::desc(PublicPropertyPublicGetter::class) => [
            PublicPropertyPublicGetter::class,
            false,
        ];

        yield self::desc(PublicSetter::class) => [
            PublicSetter::class,
            false,
        ];

        yield self::desc(ReadonlyPublicProperty::class) => [
            ReadonlyPublicProperty::class,
            true,
        ];
    }

    /**
     * @param class-string $class
     * @dataProvider provideValueObject
     */
    public function testValueObject(string $class, bool $isValueObject): void
    {
        $eagerPropertiesResolver = $this->get(EagerPropertiesResolverInterface::class);
        $propertyListExtractor = $this->get(PropertyListExtractorInterface::class);

        $propertyReadInfoExtractor = $this->get(PropertyReadInfoExtractorInterface::class);
        $propertyWriteInfoExtractor = $this->get(PropertyWriteInfoExtractorInterface::class);

        $propertyAccessInfoExtractor = new PropertyAccessInfoExtractor(
            propertyReadInfoExtractor: $propertyReadInfoExtractor,
            propertyWriteInfoExtractor: $propertyWriteInfoExtractor,
        );

        $attributesExtractor = new AttributesExtractor(
            propertyAccessInfoExtractor: $propertyAccessInfoExtractor,
        );

        $valueObjectDeterminer = new ValueObjectDeterminer(
            propertyListExtractor: $propertyListExtractor,
            propertyAccessInfoExtractor: $propertyAccessInfoExtractor,
            dynamicPropertiesDeterminer: new DynamicPropertiesDeterminer(),
            attributesExtractor: $attributesExtractor,
        );

        $classMetadataFactory = new ClassMetadataFactory(
            eagerPropertiesResolver: $eagerPropertiesResolver,
            dynamicPropertiesDeterminer: new DynamicPropertiesDeterminer(),
            attributesExtractor: $attributesExtractor,
            valueObjectDeterminer: $valueObjectDeterminer,
        );

        $classMetadata = $classMetadataFactory->createClassMetadata($class);

        $this->assertSame($isValueObject, $classMetadata->isValueObject());
    }

    private static function desc(string $class): string
    {
        return preg_replace('|^.*\W|', '', $class) ?? $class;
    }
}
