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

namespace Rekalogika\Mapper\Tests\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Contracts\Service\ResetInterface;

#[AsDecorator(LoggerInterface::class)]
class TestLogger implements LoggerInterface, ResetInterface
{
    /**
     * @var list<string>
     */
    private array $messages = [];

    public function __construct(
        #[AutowireDecorated()]
        private readonly LoggerInterface $logger,
    ) {}

    public function reset(): void
    {
        $this->messages = [];
    }

    public function isInMessage(string $string): bool
    {
        foreach ($this->messages as $message) {
            if (str_contains($message, $string)) {
                return true;
            }
        }

        return false;
    }

    private function isSuppressed(string|\Stringable $message): bool
    {
        return str_contains((string) $message, 'has a mapping involving an invalid class');
    }

    #[\Override]
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->emergency($message, $context);
            $this->messages[] = (string) $message;
        }
    }

    #[\Override]
    public function alert(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->alert($message, $context);
            $this->messages[] = (string) $message;
        }
    }

    #[\Override]
    public function critical(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->critical($message, $context);
            $this->messages[] = (string) $message;
        }
    }

    #[\Override]
    public function error(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->error($message, $context);
            $this->messages[] = (string) $message;
        }
    }

    #[\Override]
    public function warning(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->warning($message, $context);
            $this->messages[] = (string) $message;
        }
    }

    #[\Override]
    public function notice(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->notice($message, $context);
            $this->messages[] = (string) $message;
        }
    }

    #[\Override]
    public function info(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->info($message, $context);
            $this->messages[] = (string) $message;
        }
    }

    #[\Override]
    public function debug(string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->debug($message, $context);
            $this->messages[] = (string) $message;
        }
    }

    #[\Override]
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        if (!$this->isSuppressed($message)) {
            $this->logger->log($level, $message, $context);
            $this->messages[] = (string) $message;
        }
    }
}
