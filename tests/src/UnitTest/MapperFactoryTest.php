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

namespace Rekalogika\Mapper\Tests\UnitTest;

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\MapperFactory;
use Rekalogika\Mapper\Tests\Fixtures\Basic\Person;
use Rekalogika\Mapper\Tests\Fixtures\Basic\PersonDto;

class MapperFactoryTest extends TestCase
{
    public function testMapperFactory(): void
    {
        $mapperFactory = new MapperFactory();
        $mapper = $mapperFactory->getMapper();

        $source = new Person('John Doe', 30);
        $target = $mapper->map($source, PersonDto::class);

        $this->assertSame('John Doe', $target->name);
        $this->assertSame(30, $target->age);
    }
}
