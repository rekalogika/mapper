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

namespace Rekalogika\Mapper\Tests\PHPUnit;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;

class MapperPreparationStartedSubscriber implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        $test = $event->test();

        if ($test instanceof TestMethod) {
            PHPUnitProfilerBundle::$testClass = $test->className();
            PHPUnitProfilerBundle::$testMethod = $test->methodName();
            PHPUnitProfilerBundle::$testId = $test->id();
        }
    }
}
