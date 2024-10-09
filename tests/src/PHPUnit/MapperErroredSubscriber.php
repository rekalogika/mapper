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

use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;

class MapperErroredSubscriber implements ErroredSubscriber
{
    #[\Override]
    public function notify(Errored $event): void
    {
        $exception = $event->throwable();

        PHPUnitProfilerBundle::$lastError = $exception;
    }
}
