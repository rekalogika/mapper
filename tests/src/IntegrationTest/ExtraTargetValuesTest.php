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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\ExtraTargetValues;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\ExtraTargetValues\SomeObject;
use Rekalogika\Mapper\Tests\Fixtures\ExtraTargetValues\SomeObjectWithConstructorDto;
use Rekalogika\Mapper\Tests\Fixtures\ExtraTargetValues\SomeObjectWithPropertyDto;
use Rekalogika\Mapper\Transformer\Exception\ExtraTargetPropertyNotFoundException;

class ExtraTargetValuesTest extends FrameworkTestCase
{
    public function testExtraTargetValuesOnConstructor(): void
    {
        $target = $this->mapper->map(
            source: new SomeObject(),
            target: SomeObjectWithConstructorDto::class,
            context: Context::create(
                new ExtraTargetValues([
                    SomeObjectWithConstructorDto::class => [
                        'date' => new \DateTimeImmutable('2021-01-01'),
                    ],
                ]),
            ),
        );

        $this->assertSame('someProperty', $target->property);
        $this->assertInstanceOf(\DateTimeImmutable::class, $target->date);
        $this->assertSame('2021-01-01', $target->date->format('Y-m-d'));
    }

    public function testExtraTargetValuesOnProperty(): void
    {
        $target = $this->mapper->map(
            source: new SomeObject(),
            target: SomeObjectWithPropertyDto::class,
            context: Context::create(
                new ExtraTargetValues([
                    SomeObjectWithPropertyDto::class => [
                        'date' => new \DateTimeImmutable('2021-01-01'),
                    ],
                ]),
            ),
        );

        $this->assertSame('someProperty', $target->property);
        $this->assertInstanceOf(\DateTimeImmutable::class, $target->date);
        $this->assertSame('2021-01-01', $target->date->format('Y-m-d'));
    }

    public function testInvalidExtraTargetValues(): void
    {
        $this->expectException(ExtraTargetPropertyNotFoundException::class);

        $this->mapper->map(
            source: new SomeObject(),
            target: SomeObjectWithConstructorDto::class,
            context: Context::create(
                new ExtraTargetValues([
                    SomeObjectWithConstructorDto::class => [
                        'foo' => new \DateTimeImmutable('2021-01-01'),
                    ],
                ]),
            ),
        );
    }
}
