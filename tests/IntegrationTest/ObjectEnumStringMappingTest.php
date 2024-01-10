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

use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\ClassImplementingStringable;
use Rekalogika\Mapper\Tests\Fixtures\SomeBackedEnum;
use Rekalogika\Mapper\Tests\Fixtures\SomeEnum;

class ObjectEnumStringMappingTest extends AbstractIntegrationTest
{
    public function testObjectToString(): void
    {
        $object = new ClassImplementingStringable();
        $result = $this->mapper->map($object, 'string');

        $this->assertSame('foo', $result);
    }

    public function testBackedEnumToString(): void
    {
        $enum = SomeBackedEnum::Foo;
        $result = $this->mapper->map($enum, 'string');

        $this->assertSame('foo', $result);
    }

    public function testUnitEnumToString(): void
    {
        $enum = SomeEnum::Foo;
        $result = $this->mapper->map($enum, 'string');

        $this->assertSame('Foo', $result);
    }
}
