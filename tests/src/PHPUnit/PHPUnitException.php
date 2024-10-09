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

class PHPUnitException extends \Exception
{
    private readonly string $retryCommand;

    public function __construct(
        string $argv0,
        private readonly string $testId,
        private readonly Throwable $phpunitThrowable,
    ) {
        // escapeshellarg(\escapeshellcmd()) is probably need because of phpunit
        // peculiarities
        $this->retryCommand = \sprintf('%s --filter=%s', $argv0, escapeshellarg(escapeshellcmd($this->testId)));

        parent::__construct(\sprintf('An error occurred during test "%s": %s', $this->testId, $phpunitThrowable->message()));
    }

    public function getPHPUnitThrowable(): Throwable
    {
        return $this->phpunitThrowable;
    }

    public function getTestId(): string
    {
        return $this->testId;
    }

    public function getFilterArguments(): string
    {
        return $this->retryCommand;
    }
}
