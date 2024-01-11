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

namespace Rekalogika\Mapper\Exception;

use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class MapperReturnsUnexpectedValueException extends UnexpectedValueException
{
    public function __construct(Type|null $type, mixed $target)
    {
        $message = sprintf(
            'Mapper returns unexpected value. Expected type "%s", but got "%s"',
            $type === null ? 'unknown' : TypeUtil::getTypeString($type),
            get_debug_type($target),
        );

        parent::__construct($message);
    }
}
