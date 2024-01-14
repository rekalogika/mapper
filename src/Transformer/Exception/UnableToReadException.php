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

class UnableToReadException extends NotMappableValueException
{
    public function __construct(
        mixed $source,
        mixed $target,
        mixed $object,
        string $propertyName,
        \Throwable $previous = null,
        Context $context = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Trying to map source type "%s" to target type "%s", but encountered an error when trying to read from the property "%s" on object type "%s".',
                \get_debug_type($source),
                \get_debug_type($target),
                $propertyName,
                \get_debug_type($object)
            ),
            previous: $previous,
            context: $context,
        );
    }
}
