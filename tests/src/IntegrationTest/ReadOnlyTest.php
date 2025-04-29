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
use Rekalogika\Mapper\Tests\Fixtures\ReadOnly\FinalReadOnlyObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\ReadOnly\ReadOnlyObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\ReadOnly\Source;
use Symfony\Component\VarExporter\LazyObjectInterface;

class ReadOnlyTest extends FrameworkTestCase
{
    public function testToFinalReadOnly(): void
    {
        $source = new Source('foo');
        $target = $this->mapper->map($source, FinalReadOnlyObjectDto::class);
        $this->assertNotInstanceOf(LazyObjectInterface::class, $target);
        $this->assertSame('foo', $target->name);
    }

    /**
     * In PHP 8.2, readonly class can't be lazy
     * @requires PHP >= 8.3.0
     */
    public function testToReadOnly(): void
    {
        $source = new Source('foo');
        $target = $this->mapper->map($source, ReadOnlyObjectDto::class);
        $this->assertIsUninitializedProxy($target);
        $this->assertSame('foo', $target->name);
    }

    // Previously, existing target readonly object is ignored, and the
    // transformation does not involve the target object. Now, the target object
    // is no longer ignored, and the transformation will involve the target
    // readonly object.

    // public function testToExistingFinalReadOnly(): void
    // {
    //     $source = new Source('foo');
    //     $target = new FinalReadOnlyObjectDto('bar');
    //     $newTarget = $this->mapper->map($source, $target);
    //     $this->assertNotInstanceOf(LazyObjectInterface::class, $newTarget);
    //     $this->assertSame('foo', $newTarget->name);
    // }

    // /**
    //  * In PHP 8.2, readonly class can't be lazy
    //  * @requires PHP >= 8.3.0
    //  */
    // public function testToExistingReadOnly(): void
    // {
    //     $source = new Source('foo');
    //     $target = new ReadOnlyObjectDto('bar');
    //     $newTarget = $this->mapper->map($source, $target);
    //     $this->assertInstanceOf(LazyObjectInterface::class, $newTarget);
    //     $this->assertSame('foo', $newTarget->name);
    // }
}
