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
use Rekalogika\Mapper\Tests\Fixtures\Money\MoneyDto;
use Rekalogika\Mapper\Tests\Fixtures\Transformer\MoneyToMoneyDtoTransformer;

class CustomTransformerTest extends AbstractIntegrationTest
{
    protected function getAdditionalTransformers(): array
    {
        return [
            'MoneyToMoneyDtoTransformer' => new MoneyToMoneyDtoTransformer(),
        ];
    }

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
}
