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

namespace Rekalogika\Mapper\Transformer\Exception;

use Rekalogika\Mapper\Context\Context;

class InternalClassUnsupportedException extends NotMappableValueException
{
    /**
     * @param class-string $class
     */
    public function __construct(
        string $class,
        ?\Throwable $previous = null,
        ?Context $context = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Trying to map an internal class "%s" which is not supported.',
                $class
            ),
            previous: $previous,
            context: $context,
        );
    }
}
