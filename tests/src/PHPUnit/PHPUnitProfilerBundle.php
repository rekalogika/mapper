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

use PHPUnit\Event\Code\Throwable;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Stopwatch\Stopwatch;

class PHPUnitProfilerBundle extends Bundle
{
    public static string $testClass = 'unknown';

    public static string $testMethod = 'unknown';

    public static string $testId = 'unknown';

    public static ?string $stopwatchToken = null;

    public static ?Throwable $lastError = null;

    #[\Override]
    public function boot(): void
    {
        $stopwatch = $this->container?->get('debug.stopwatch');
        \assert($stopwatch instanceof Stopwatch);

        self::$stopwatchToken = substr(hash('xxh128', uniqid((string) mt_rand(), true)), 0, 6);
        $stopwatch->openSection();
    }

    #[\Override]
    public function shutdown(): void
    {
        $argv = $_SERVER['argv'] ?? null;
        \assert(\is_array($argv));
        $argv0 = $argv[0];
        /** @var array<int,string> $argv */

        $stopwatch = $this->container?->get('debug.stopwatch');
        \assert($stopwatch instanceof Stopwatch);

        if (self::$stopwatchToken !== null) {
            $stopwatch->stopSection(self::$stopwatchToken);
        }

        $request = new TestRequest(
            argv: $argv,
            testClass: self::$testClass,
            testMethod: self::$testMethod,
            hasError: self::$lastError !== null,
        );

        $request->attributes->set('_stopwatch_token', self::$stopwatchToken);

        $profiler = $this->container?->get('profiler');
        \assert($profiler instanceof Profiler);

        $profile = $profiler->collect(
            request: $request,
            response: $request->getResponse(),
            exception: self::$lastError !== null ? new PHPUnitException(
                // @phpstan-ignore argument.type
                argv0: $argv0,
                testId: self::$testId,
                phpunitThrowable: self::$lastError,
            ) : null,
        );

        if ($profile !== null) {
            $profiler->saveProfile($profile);
        }

        self::$lastError = null;
        self::$testClass = 'unknown';
        self::$testMethod = 'unknown';
        self::$testId = 'unknown';
        self::$stopwatchToken = null;
    }
}
