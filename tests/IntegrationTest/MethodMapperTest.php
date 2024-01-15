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
use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\MethodMapper\MoneyDto;
use Rekalogika\Mapper\Tests\Fixtures\MethodMapper\ObjectWithArrayPropertyDto;
use Rekalogika\Mapper\Tests\Fixtures\MethodMapper\ObjectWithCollectionProperty;
use Rekalogika\Mapper\Tests\Fixtures\MethodMapper\ObjectWithObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\MethodMapper\ObjectWithObjectWithScalarPropertiesDto;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;

class MethodMapperTest extends AbstractIntegrationTest
{
    public function testMoneyToMoneyDto(): void
    {
        $money = Money::of('100.00', 'USD');
        $result = $this->mapper->map($money, MoneyDto::class);

        $this->assertInstanceOf(MoneyDto::class, $result);
        $this->assertSame('100.00', $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
    }

    public function testMoneyDtoToMoney(): void
    {
        $moneyDto = new MoneyDto('100.00', 'USD');
        $result = $this->mapper->map($moneyDto, Money::class);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertSame('100.00', $result->getAmount()->__toString());
        $this->assertSame('USD', $result->getCurrency()->getCurrencyCode());
    }

    public function testSubMapperToDto(): void
    {
        $objectWithObjectWithScalarProperties = new ObjectWithObjectWithScalarProperties();

        $result = $this->mapper->map(
            $objectWithObjectWithScalarProperties,
            ObjectWithObjectWithScalarPropertiesDto::class
        );

        $this->assertInstanceOf(ObjectWithObjectWithScalarPropertiesDto::class, $result);
        $this->assertEquals(
            $objectWithObjectWithScalarProperties->objectWithScalarProperties->a,
            $result->objectWithScalarProperties?->a
        );
        $this->assertEquals(
            $objectWithObjectWithScalarProperties->objectWithScalarProperties->b,
            $result->objectWithScalarProperties?->b
        );
        $this->assertEquals(
            $objectWithObjectWithScalarProperties->objectWithScalarProperties->c,
            $result->objectWithScalarProperties?->c
        );
        $this->assertEquals(
            $objectWithObjectWithScalarProperties->objectWithScalarProperties->d,
            $result->objectWithScalarProperties?->d
        );
    }

    public function testSubMapperFromDto(): void
    {
        $objectWithObjectWithScalarPropertiesDto = new ObjectWithObjectWithScalarPropertiesDto();
        $objectWithScalarPropertiesDto = new ObjectWithScalarPropertiesDto();
        $objectWithScalarPropertiesDto->a = 123;
        $objectWithScalarPropertiesDto->b = 'foo';
        $objectWithScalarPropertiesDto->c = false;
        $objectWithScalarPropertiesDto->d = 123.45;

        $objectWithObjectWithScalarPropertiesDto->objectWithScalarProperties = $objectWithScalarPropertiesDto;

        $target = new ObjectWithObjectWithScalarProperties();

        $result = $this->mapper->map(
            $objectWithObjectWithScalarPropertiesDto,
            $target,
        );

        $this->assertInstanceOf(ObjectWithObjectWithScalarProperties::class, $result);
        $this->assertEquals(
            $objectWithObjectWithScalarPropertiesDto->objectWithScalarProperties->a,
            $result->objectWithScalarProperties->a
        );
        $this->assertEquals(
            $objectWithObjectWithScalarPropertiesDto->objectWithScalarProperties->b,
            $result->objectWithScalarProperties->b
        );
        $this->assertEquals(
            $objectWithObjectWithScalarPropertiesDto->objectWithScalarProperties->c,
            $result->objectWithScalarProperties->c
        );
        $this->assertEquals(
            $objectWithObjectWithScalarPropertiesDto->objectWithScalarProperties->d,
            $result->objectWithScalarProperties->d
        );
    }

    public function testMapForProperty(): void
    {
        $source = new ObjectWithCollectionProperty();
        $result = $this->mapper->map(
            $source,
            ObjectWithArrayPropertyDto::class,
        );

        $this->assertInstanceOf(ObjectWithArrayPropertyDto::class, $result);
        $this->assertIsArray($result->property);
        $this->assertCount(3, $result->property);
    }

}
