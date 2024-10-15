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
use Rekalogika\Mapper\Tests\Fixtures\Issue232\BigDecimalSource;
use Rekalogika\Mapper\Tests\Fixtures\Issue232\BigDecimalTarget;
use Rekalogika\Mapper\Tests\Fixtures\Issue232\CurrencySource;
use Rekalogika\Mapper\Tests\Fixtures\Issue232\CurrencyTarget;

class Issue232Test extends FrameworkTestCase
{
    public function testBigDecimal(): void
    {
        $source = new BigDecimalSource('100');
        $target = $this->mapper->map($source, BigDecimalTarget::class);

        $this->assertEquals('100', $target->amount->toScale(0)->jsonSerialize());
    }

    public function testCurrency(): void
    {
        $source = new CurrencySource('USD');
        $target = $this->mapper->map($source, CurrencyTarget::class);

        $this->assertEquals('USD', $target->currency->getCurrencyCode());
    }
}
