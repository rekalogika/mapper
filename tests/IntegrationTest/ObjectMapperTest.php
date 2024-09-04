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
use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\FinalPersonDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForProxy;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyObjectMapper;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\Person;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\PersonDto;
use Rekalogika\Mapper\Transformer\Implementation\ObjectMapperTransformer;
use Symfony\Component\VarExporter\LazyObjectInterface;

class ObjectMapperTest extends FrameworkTestCase
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

    public function testMoneyToMoneyDtoProxy(): void
    {
        $money = Money::of('100.00', 'USD');
        $result = $this->mapper->map($money, MoneyDtoForProxy::class);

        $this->assertInstanceOf(MoneyDtoForProxy::class, $result);
        $this->assertInstanceOf(LazyObjectInterface::class, $result);

        $this->assertFalse($result->isLazyObjectInitialized());
        $this->assertSame('100.00', $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
        $this->assertTrue($result->isLazyObjectInitialized());
    }

    public function testProxyWithEagerProperty(): void
    {
        $person = new Person('1', 'John Doe');
        $result = $this->mapper->map($person, PersonDto::class);

        $this->assertInstanceOf(LazyObjectInterface::class, $result);
        $this->assertFalse($result->isLazyObjectInitialized());
        $this->assertTrue($person->isGetIdIsCalled());
        $this->assertFalse($person->isGetNameIsCalled());

        $this->assertSame('1', $result->id);

        $this->assertTrue($person->isGetIdIsCalled());
        $this->assertFalse($person->isGetNameIsCalled());
        $this->assertFalse($result->isLazyObjectInitialized());

        $this->assertSame('John Doe', $result->name);

        $this->assertTrue($person->isGetIdIsCalled());
        $this->assertTrue($person->isGetNameIsCalled());
        $this->assertTrue($result->isLazyObjectInitialized());
    }

    public function testErrorFinalTarget(): void
    {
        $person = new Person('1', 'John Doe');
        $this->expectException(ProxyNotSupportedException::class);
        $result = $this->mapper->map($person, FinalPersonDto::class);
    }

}
