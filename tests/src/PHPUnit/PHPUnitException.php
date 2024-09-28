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
    private string $testId;
    private string $retryCommand;

    public function __construct(
        string $argv0,
        string $id,
        private Throwable $phpunitThrowable,
    ) {
        $this->testId = $id;

        // escapeshellarg(\escapeshellcmd()) is probably need because of phpunit
        // peculiarities
        $this->retryCommand = \sprintf('%s --filter=%s', $argv0, escapeshellarg(escapeshellcmd($id)));

        parent::__construct(\sprintf('An error occurred during test "%s": %s', $id, $phpunitThrowable->message()));
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
