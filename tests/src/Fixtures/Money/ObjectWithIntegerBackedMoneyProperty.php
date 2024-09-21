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

namespace Rekalogika\Mapper\Tests\Fixtures\Money;

use Brick\Money\Money;

class ObjectWithIntegerBackedMoneyProperty
{
    private int $amount = 0;

    private string $currency = 'USD';

    public function setAmount(Money $amount): void
    {
        $this->amount = $amount->getMinorAmount()->toInt();
        $this->currency = $amount->getCurrency()->getCurrencyCode();
    }

    public function getAmount(): Money
    {
        return Money::ofMinor($this->amount, $this->currency);
    }
}
