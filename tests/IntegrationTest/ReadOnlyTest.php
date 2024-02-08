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

use Rekalogika\Mapper\Tests\Common\AbstractFrameworkTest;
use Rekalogika\Mapper\Tests\Fixtures\ReadOnly\FinalReadOnlyObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\ReadOnly\ReadOnlyObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\ReadOnly\Source;
use Symfony\Component\VarExporter\LazyObjectInterface;

class ReadOnlyTest extends AbstractFrameworkTest
{
    public function testToFinalReadOnly(): void
    {
        $source = new Source('foo');
        $target = $this->mapper->map($source, FinalReadOnlyObjectDto::class);
        $this->assertNotInstanceOf(LazyObjectInterface::class, $target);
        $this->assertSame('foo', $target->name);
    }

    public function testToReadOnly(): void
    {
        $source = new Source('foo');
        $target = $this->mapper->map($source, ReadOnlyObjectDto::class);
        $this->assertInstanceOf(LazyObjectInterface::class, $target);
        $this->assertSame('foo', $target->name);
    }

    public function testToExistingFinalReadOnly(): void
    {
        $source = new Source('foo');
        $target = new FinalReadOnlyObjectDto('bar');
        $newTarget = $this->mapper->map($source, $target);
        $this->assertNotInstanceOf(LazyObjectInterface::class, $newTarget);
        $this->assertSame('foo', $newTarget->name);
    }

    public function testToExistingReadOnly(): void
    {
        $source = new Source('foo');
        $target = new ReadOnlyObjectDto('bar');
        $newTarget = $this->mapper->map($source, $target);
        $this->assertInstanceOf(LazyObjectInterface::class, $newTarget);
        $this->assertSame('foo', $newTarget->name);
    }

}
