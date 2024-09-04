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

class UnableToWriteException extends NotMappableValueException
{
    public function __construct(
        mixed $target,
        string $propertyName,
        \Throwable $previous = null,
        Context $context = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Encountered an error when trying to write to the property "%s" on object type "%s".',
                $propertyName,
                get_debug_type($target),
            ),
            previous: $previous,
            context: $context,
        );
    }
}
