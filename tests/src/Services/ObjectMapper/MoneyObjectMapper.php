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

namespace Rekalogika\Mapper\Tests\Services\ObjectMapper;

use Brick\Money\Money;
use Rekalogika\Mapper\Attribute\AsObjectMapper;
use Rekalogika\Mapper\SubMapper\SubMapperInterface;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForProxy;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForTargetInvalidTypeHint;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForTargetModification;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForTargetReplacement;

class MoneyObjectMapper
{
    #[AsObjectMapper]
    public function mapMoneyToMoneyDto(Money $money): MoneyDto
    {
        return new MoneyDto(
            $money->getAmount()->__toString(),
            $money->getCurrency()->getCurrencyCode(),
        );
    }

    #[AsObjectMapper]
    public function mapMoneyDtoToMoney(MoneyDto $moneyDto): Money
    {
        return Money::of($moneyDto->getAmount(), $moneyDto->getCurrency());
    }

    #[AsObjectMapper]
    public function mapMoneyToMoneyDtoForProxy(
        Money $money,
        SubMapperInterface $subMapper,
    ): MoneyDtoForProxy {
        return $subMapper->createProxy(
            MoneyDtoForProxy::class,
            static function (MoneyDtoForProxy $proxy) use ($money): void {
                /** @psalm-suppress DirectConstructorCall */
                $proxy->__construct(
                    $money->getAmount()->__toString(),
                    $money->getCurrency()->getCurrencyCode(),
                );
            },
        );
    }

    #[AsObjectMapper]
    public function mapMoneyToMoneyDtoForTargetModification(
        Money $money,
        MoneyDtoForTargetModification $currentValue,
    ): MoneyDtoForTargetModification {
        $currentValue->setAmount($money->getAmount()->__toString());
        $currentValue->setCurrency($money->getCurrency()->getCurrencyCode());

        return $currentValue;
    }

    #[AsObjectMapper]
    public function mapMoneyToMoneyDtoForTargetReplacement(
        Money $money,
        MoneyDtoForTargetReplacement $currentValue,
    ): MoneyDtoForTargetReplacement {
        return $currentValue
            ->withAmount($money->getAmount()->__toString())
            ->withCurrency($money->getCurrency()->getCurrencyCode());
    }

    #[AsObjectMapper]
    public function mapMoneyToMoneyDtoForInvalidTypeHint(
        Money $money,
        \DateTime $currentValue,
    ): MoneyDtoForTargetInvalidTypeHint {
        throw new \RuntimeException('This method should not be called');
    }
}
