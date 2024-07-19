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

namespace Rekalogika\Mapper\Tests\Service;

use Psr\Log\LoggerInterface;

class TestLogger implements LoggerInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    private function isSuppressed(string|\Stringable $message): bool
    {
        return str_contains((string)$message, 'has a mapping involving an invalid class');
    }

    #[\Override]
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->emergency($message, $context);
        }
    }

    #[\Override]
    public function alert(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->alert($message, $context);
        }
    }

    #[\Override]
    public function critical(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->critical($message, $context);
        }
    }

    #[\Override]
    public function error(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->error($message, $context);
        }
    }

    #[\Override]
    public function warning(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->warning($message, $context);
        }
    }

    #[\Override]
    public function notice(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->notice($message, $context);
        }
    }

    #[\Override]
    public function info(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->info($message, $context);
        }
    }

    #[\Override]
    public function debug(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->debug($message, $context);
        }
    }

    #[\Override]
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->log($level, $message, $context);
        }
    }
}
