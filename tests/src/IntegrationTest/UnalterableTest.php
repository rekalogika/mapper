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

use Brick\Money\Money;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Unalterable\PublicGetter;
use Rekalogika\Mapper\Tests\Fixtures\Unalterable\PublicPropertyPublicGetter;
use Rekalogika\Mapper\Tests\Fixtures\Unalterable\PublicSetter;
use Rekalogika\Mapper\Tests\Fixtures\Unalterable\ReadonlyPublicProperty;
use Rekalogika\Mapper\Tests\Fixtures\WitherMethod\ObjectWithImmutableSetter;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractor\AttributesExtractor;
use Rekalogika\Mapper\Transformer\MetadataUtil\ClassMetadataFactory\ClassMetadataFactory;
use Rekalogika\Mapper\Transformer\MetadataUtil\DynamicPropertiesDeterminer\DynamicPropertiesDeterminer;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyAccessInfoExtractor\PropertyAccessInfoExtractor;
use Rekalogika\Mapper\Transformer\MetadataUtil\UnalterableDeterminer;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

class UnalterableTest extends FrameworkTestCase
{
    /**
     * @return iterable<array-key,array{class-string,bool}>
     */
    public static function provideUnalterable(): iterable
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

        yield self::desc(Money::class) => [
            Money::class,
            true,
        ];

        yield self::desc(\DateTimeImmutable::class) => [
            \DateTimeImmutable::class,
            true,
        ];

        yield self::desc(Collection::class) => [
            Collection::class,
            false,
        ];

        yield self::desc(ReadableCollection::class) => [
            ReadableCollection::class,
            false,
        ];
    }

    /**
     * @param class-string $class
     * @dataProvider provideUnalterable
     */
    public function testUnalterable(string $class, bool $isUnalterable): void
    {
        $eagerPropertiesResolver = $this->get(EagerPropertiesResolverInterface::class);
        $propertyListExtractor = $this->get(PropertyListExtractorInterface::class);

        $propertyReadInfoExtractor = $this->get(PropertyReadInfoExtractorInterface::class);

        $propertyWriteInfoExtractor = $this->get(PropertyWriteInfoExtractorInterface::class);

        $propertyTypeExtractor = $this->get(PropertyTypeExtractorInterface::class);

        $propertyAccessInfoExtractor = new PropertyAccessInfoExtractor(
            propertyReadInfoExtractor: $propertyReadInfoExtractor,
            propertyWriteInfoExtractor: $propertyWriteInfoExtractor,
        );

        $attributesExtractor = new AttributesExtractor(
            propertyAccessInfoExtractor: $propertyAccessInfoExtractor,
        );

        $dynamicPropertiesDeterminer = new DynamicPropertiesDeterminer();

        $unalterableDeterminer = new UnalterableDeterminer(
            propertyListExtractor: $propertyListExtractor,
            propertyAccessInfoExtractor: $propertyAccessInfoExtractor,
            dynamicPropertiesDeterminer: $dynamicPropertiesDeterminer,
            attributesExtractor: $attributesExtractor,
            propertyTypeExtractor: $propertyTypeExtractor,
        );

        $classMetadataFactory = new ClassMetadataFactory(
            eagerPropertiesResolver: $eagerPropertiesResolver,
            dynamicPropertiesDeterminer: $dynamicPropertiesDeterminer,
            attributesExtractor: $attributesExtractor,
            unalterableDeterminer: $unalterableDeterminer,
        );

        $classMetadata = $classMetadataFactory->createClassMetadata($class);

        $this->assertSame($isUnalterable, $classMetadata->isUnalterable());
    }

    private static function desc(string $class): string
    {
        return preg_replace('|^.*\W|', '', $class) ?? $class;
    }
}
