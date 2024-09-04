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
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class InvalidTypeInArgumentException extends InvalidArgumentException
{
    public function __construct(
        string $printfMessage,
        ?Type $expectedType,
        Context $context = null,
    ) {
        parent::__construct(
            message: \sprintf($printfMessage, TypeUtil::getDebugType($expectedType)),
            context: $context,
        );
    }
}
