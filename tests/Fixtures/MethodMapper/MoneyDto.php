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

namespace Rekalogika\Mapper\Tests\Fixtures\MethodMapper;

use Brick\Money\Money;
use Rekalogika\Mapper\MainTransformer\Context;
use Rekalogika\Mapper\MethodMapper\MapFromObjectInterface;
use Rekalogika\Mapper\MethodMapper\MapToObjectInterface;
use Rekalogika\Mapper\MethodMapper\SubMapperInterface;

final class MoneyDto implements MapToObjectInterface, MapFromObjectInterface
{
    public function __construct(
        private string $amount,
        private string $currency,
    ) {
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function mapToObject(
        object|string $target,
        SubMapperInterface $mapper,
        Context $context,
    ): object {
        return Money::of($this->amount, $this->currency);
    }

    public static function mapFromObject(
        object $source,
        SubMapperInterface $mapper,
        Context $context
    ): static {
        if (!$source instanceof Money) {
            throw new \InvalidArgumentException('Source must be instance of ' . Money::class);
        }

        return new static(
            $source->getAmount()->__toString(),
            $source->getCurrency()->getCurrencyCode(),
        );
    }
}
