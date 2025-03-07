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
use Rekalogika\Mapper\Exception\LogicException;

/**
 * @internal
 */
final class ExtraTargetPropertyNotFoundException extends LogicException
{
    /**
     * @param class-string $class
     */
    public function __construct(
        string $class,
        string $property,
        Context $context,
    ) {
        $message = \sprintf(
            'Mapper is called with "ExtraTargetValues", but cannot find the target property "%s" in class "%s"',
            $property,
            $class,
        );

        parent::__construct($message, context: $context);
    }
}
