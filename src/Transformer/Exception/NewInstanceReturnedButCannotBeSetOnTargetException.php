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

class NewInstanceReturnedButCannotBeSetOnTargetException extends NotMappableValueException
{
    public function __construct(
        mixed $target,
        string $propertyName,
        ?\Throwable $previous = null,
        ?Context $context = null,
    ) {
        parent::__construct(
            message: \sprintf(
                'Transformation of property "%s" on object type "%s" results in a different object instance from the original instance, but the new instance cannot be set on the target object. You may wish to add a setter method to the target class.',
                $propertyName,
                get_debug_type($target),
            ),
            previous: $previous,
            context: $context,
        );
    }
}
