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

namespace Rekalogika\Mapper\MainTransformer\Exception;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class TransformerReturnsUnexpectedValueException extends UnexpectedValueException
{
    public function __construct(Type|MixedType $type, mixed $target, Context $context)
    {
        $message = sprintf(
            'Mapper returns unexpected value. Expected type "%s", but got "%s".',
            TypeUtil::getTypeString($type),
            get_debug_type($target),
        );

        parent::__construct($message, context: $context);
    }
}
