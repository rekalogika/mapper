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
use Rekalogika\Mapper\Tests\Common\AbstractFrameworkTest;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyObjectMapper;
use Rekalogika\Mapper\Transformer\ObjectMapperTransformer;

class ObjectMapperTest extends AbstractFrameworkTest
{
    public function testService(): void
    {
        $moneyObjectMapper = $this->get(MoneyObjectMapper::class);
        $objectMapperTransformer = $this->get('test.' . ObjectMapperTransformer::class);

        $this->assertTransformerInstanceOf(MoneyObjectMapper::class, $moneyObjectMapper);
        $this->assertTransformerInstanceOf(ObjectMapperTransformer::class, $objectMapperTransformer);
    }

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
}
