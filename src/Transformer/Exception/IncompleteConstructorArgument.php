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

class IncompleteConstructorArgument extends NotMappableValueException
{
    /**
     * @param class-string $targetClass
     */
    public function __construct(
        object $source,
        string $targetClass,
        string $property,
        \Throwable $previous = null,
        Context $context = null,
    ) {
        parent::__construct(
            message: sprintf('Trying to instantiate target class "%s", but its constructor requires the property "%s", which is missing from the source "%s".', $targetClass, $property, \get_debug_type($source)),
            previous: $previous,
            context: $context
        );
    }
}
