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

namespace Rekalogika\Mapper\Tests\Fixtures\ObjectMapper;

final readonly class MoneyDto implements MoneyDtoInterface
{
    public function __construct(
        private string $amount,
        private string $currency,
    ) {}

    #[\Override]
    public function getAmount(): string
    {
        return $this->amount;
    }

    #[\Override]
    public function getCurrency(): string
    {
        return $this->currency;
    }
}
