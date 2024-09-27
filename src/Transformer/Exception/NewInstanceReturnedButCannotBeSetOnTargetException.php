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
                'The existing target value of property "%s" on object type "%s" is a different instance from the new target value returned by the transformer. But the target object does not allow setting the value. This might indicate a limitation or bug in the transformer, custom object mapper, or property mapper. Or you can simply need to the setter method to the target object.',
                $propertyName,
                get_debug_type($target),
            ),
            previous: $previous,
            context: $context,
        );
    }
}
