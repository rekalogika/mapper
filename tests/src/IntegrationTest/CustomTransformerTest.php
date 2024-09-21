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
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Money\MoneyDto;
use Rekalogika\Mapper\Tests\Fixtures\Money\ObjectWithIntegerBackedMoneyProperty;
use Rekalogika\Mapper\Tests\Fixtures\Money\ObjectWithMoneyAmountDto;

class CustomTransformerTest extends FrameworkTestCase
{
    public function testMoneyToMoneyDto(): void
    {
        $money = Money::of("100000.00", 'IDR');
        $moneyDto = $this->mapper->map($money, MoneyDto::class);

        $this->assertSame('100000.00', $moneyDto->getAmount());
        $this->assertSame('IDR', $moneyDto->getCurrency());
    }

    public function testMoneyDtoToMoney(): void
    {
        $moneyDto = new MoneyDto('100000.00', 'IDR');
        $money = $this->mapper->map($moneyDto, Money::class);

        $this->assertSame('100000.00', $money->getAmount()->__toString());
        $this->assertSame('IDR', $money->getCurrency()->getCurrencyCode());
    }

    public function testObjectWithIntegerBackedMoneyToDto(): void
    {
        $object = new ObjectWithIntegerBackedMoneyProperty();
        $object->setAmount(Money::of("100000.00", 'IDR'));

        $dto = $this->mapper->map($object, ObjectWithMoneyAmountDto::class);

        $amount = $dto->amount;
        $this->assertInstanceOf(MoneyDto::class, $amount);
        $this->assertSame('100000.00', $amount->getAmount());
        $this->assertSame('IDR', $amount->getCurrency());
    }

    public function testObjectWithIntegerBackedMoneyFromDto(): void
    {
        $dto = new ObjectWithMoneyAmountDto();
        $dto->amount = new MoneyDto('100000.00', 'IDR');

        $object = $this->mapper->map($dto, ObjectWithIntegerBackedMoneyProperty::class);
        $amount = $object->getAmount();

        $this->assertInstanceOf(Money::class, $amount);
        $this->assertSame('100000.00', $amount->getAmount()->__toString());
        $this->assertSame('IDR', $amount->getCurrency()->getCurrencyCode());
    }
}
