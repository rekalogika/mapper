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
use Rekalogika\Mapper\Tests\Fixtures\Magic\ObjectWithMagicGet;
use Rekalogika\Mapper\Tests\Fixtures\Magic\ObjectWithMagicSet;
use Rekalogika\Mapper\Tests\Fixtures\Magic\SomeDto;

class MagicTest extends FrameworkTestCase
{
    public function testMagicGet(): void
    {
        $source = new ObjectWithMagicGet();
        $result = $this->mapper->map($source, SomeDto::class);

        $this->assertSame('Hello', $result->string);
        $this->assertSame('2021-01-01', $result->date->format('Y-m-d'));
        $this->assertFalse(isset($result->generatesException));
    }

    public function testMagicSet(): void
    {
        $source = SomeDto::prefilled();
        $result = $this->mapper->map($source, ObjectWithMagicSet::class);

        $this->assertSame('Hello', $result->getStringResult());
        $this->assertSame('2021-01-01', $result->getDateResult()->format('Y-m-d'));
    }
}
