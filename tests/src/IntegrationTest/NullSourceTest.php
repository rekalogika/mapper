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
use Rekalogika\Mapper\Tests\Fixtures\NullSource\Source;
use Rekalogika\Mapper\Tests\Fixtures\NullSource\TargetString;
use Rekalogika\Mapper\Tests\Fixtures\NullSource\TargetUuid;

class NullSourceTest extends FrameworkTestCase
{
    public function testNullSourceToUuid(): void
    {
        $source = new Source();
        $target = new TargetUuid();
        $newTarget = $this->mapper->map($source, $target);

        $this->assertSame($target, $newTarget);
    }

    public function testNullSourceToString(): void
    {
        $source = new Source();
        $target = new TargetString();
        $newTarget = $this->mapper->map($source, $target);

        $this->assertSame($target, $newTarget);
    }

}
