<?php

declare(strict_types=1);

/*
 * This file is part of rekalogika/rekapager package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

use Rekalogika\Mapper\Tests\Common\TestKernel;

require_once __DIR__ . '/../../vendor/autoload_runtime.php';

return fn(array $context): TestKernel => new TestKernel();
