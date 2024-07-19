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

namespace Rekalogika\Mapper\Proxy\Exception;

use Rekalogika\Mapper\Exception\RuntimeException;

class ProxyNotSupportedException extends RuntimeException
{
    private readonly string $reason;

    /**
     * @param class-string $class
     */
    public function __construct(
        string $class,
        ?string $reason = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                'Creating a proxy for class "%s" is not supported.',
                $class
            ),
            previous: $previous
        );

        $this->reason = $reason ?? $previous?->getMessage() ?? sprintf(
            'Reason is not provided, thrown by "%s", line %d.',
            $this->getFile(),
            $this->getLine()
        );
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
