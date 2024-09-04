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
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class InvalidClassException extends UnexpectedValueException
{
    public function __construct(
        Type $type,
        Context $context,
    ) {
        parent::__construct(
            message: \sprintf('Trying to map to class "%s", but this is not a valid class, interface, or enum.', TypeUtil::getDebugType($type)),
            context: $context,
        );
    }
}
