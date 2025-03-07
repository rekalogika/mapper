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

final class UnableToReadException extends NotMappableValueException
{
    public function __construct(
        mixed $source,
        string $property,
        ?\Throwable $previous = null,
        ?Context $context = null,
    ) {
        parent::__construct(
            message: \sprintf(
                'Encountered an error when trying to read from the property "%s" from object type "%s".',
                $property,
                get_debug_type($source),
            ),
            previous: $previous,
            context: $context,
        );
    }
}
