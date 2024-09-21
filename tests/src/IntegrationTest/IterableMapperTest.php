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
use Rekalogika\Mapper\Tests\Fixtures\Basic\Person;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonDto;

class IterableMapperTest extends FrameworkTestCase
{
    public function testAdder(): void
    {
        $result = $this->iterableMapper->mapIterable($this->getIterableInput(), PersonDto::class);
        /** @psalm-suppress InvalidArgument */
        $result = iterator_to_array($result);

        $this->assertCount(3, $result);
        $this->assertInstanceOf(PersonDto::class, $result[0]);
        $this->assertInstanceOf(PersonDto::class, $result[1]);
        $this->assertInstanceOf(PersonDto::class, $result[2]);
        $this->assertSame('John Doe', $result[0]->name);
        $this->assertSame(30, $result[0]->age);
        $this->assertSame('Jane Doe', $result[1]->name);
        $this->assertSame(25, $result[1]->age);
        $this->assertSame('Foo Bar', $result[2]->name);
        $this->assertSame(99, $result[2]->age);
    }

    /**
     * @return iterable<Person>
     */
    private function getIterableInput(): iterable
    {
        yield new Person('John Doe', 30);
        yield new Person('Jane Doe', 25);
        yield new Person('Foo Bar', 99);
    }
}
