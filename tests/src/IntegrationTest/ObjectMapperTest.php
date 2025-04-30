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
use PHPUnit\Framework\Attributes\RequiresPhp;
use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\Baz;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\FinalPersonDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\Foo;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForProxy;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForTargetInvalidTypeHint;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForTargetModification;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoForTargetReplacement;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoInterface;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\MoneyDtoToo;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\Person;
use Rekalogika\Mapper\Tests\Fixtures\ObjectMapper\PersonDto;
use Rekalogika\Mapper\Tests\Services\ObjectMapper\MoneyObjectMapper;
use Rekalogika\Mapper\Transformer\Implementation\ObjectMapperTransformer;

class ObjectMapperTest extends FrameworkTestCase
{
    public function testService(): void
    {
        $moneyObjectMapper = $this->get(MoneyObjectMapper::class);
        $objectMapperTransformer = $this->get(ObjectMapperTransformer::class);

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

    public function testMoneyToMoneyDtoInterface(): void
    {
        $money = Money::of('100.00', 'USD');
        $result = $this->mapper->map($money, MoneyDtoInterface::class);

        $this->assertInstanceOf(MoneyDtoInterface::class, $result);
        $this->assertInstanceOf(MoneyDto::class, $result);
        $this->assertSame('100.00', $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
    }

    public function testMoneyDtoInterfaceToMoney(): void
    {
        $moneyDto = new MoneyDtoToo('100.00', 'USD');
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
        $this->assertIsUninitializedProxy($result);
        $this->assertSame('100.00', $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
        $this->assertNotUninitializedProxy($result);
    }

    public function testMoneyToMoneyDtoForTargetModification(): void
    {
        $money = Money::of('100.00', 'USD');
        $target = new MoneyDtoForTargetModification('10000000.00', 'IDR');
        $result = $this->mapper->map($money, $target);

        $this->assertInstanceOf(MoneyDtoForTargetModification::class, $result);

        $this->assertEquals('100.00', $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
        $this->assertSame($target, $result);
    }

    public function testMoneyToMoneyDtoForTargetReplacement(): void
    {
        $money = Money::of('100.00', 'USD');
        $target = new MoneyDtoForTargetReplacement('10000000.00', 'IDR');
        $result = $this->mapper->map($money, $target);

        $this->assertInstanceOf(MoneyDtoForTargetReplacement::class, $result);

        $this->assertEquals('100.00', $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
        $this->assertNotSame($target, $result);
    }

    public function testMoneyToMoneyDtoForInvalidTypeHint(): void
    {
        $this->expectException(\TypeError::class);
        $money = Money::of('100.00', 'USD');
        $target = new MoneyDtoForTargetInvalidTypeHint('10000000.00', 'IDR');
        $result = $this->mapper->map($money, $target);
    }

    public function testProxyWithEagerProperty(): void
    {
        $person = new Person('1', 'John Doe');
        $result = $this->mapper->map($person, PersonDto::class);

        $this->assertIsUninitializedProxy($result);
        $this->assertTrue($person->isGetIdIsCalled());
        $this->assertFalse($person->isGetNameIsCalled());

        $this->assertSame('1', $result->id);

        $this->assertTrue($person->isGetIdIsCalled());
        $this->assertFalse($person->isGetNameIsCalled());
        $this->assertIsUninitializedProxy($result);

        $this->assertSame('John Doe', $result->name);

        $this->assertTrue($person->isGetIdIsCalled());
        $this->assertTrue($person->isGetNameIsCalled());
        $this->assertNotUninitializedProxy($result);
    }

    /**
     * PHP lazy objects support final objects
     */
    #[RequiresPhp('< 8.4')]
    public function testErrorFinalTarget(): void
    {
        $person = new Person('1', 'John Doe');
        $this->expectException(ProxyNotSupportedException::class);
        $result = $this->mapper->map($person, FinalPersonDto::class);
    }

    public function testUnionType(): void
    {
        $foo = new Foo();
        $result = $this->mapper->map($foo, Baz::class);

        $this->assertInstanceOf(Baz::class, $result);
    }

    public function testUnalterableExistingTarget(): void
    {
        $baz = new Baz();
        $foo = new Foo();

        $result = $this->mapper->map($baz, $foo);
        $this->assertNotSame($foo, $result);
    }
}
