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

class NonSimpleTypeException extends UnexpectedValueException
{
    public function __construct(Type $type)
    {
        parent::__construct(sprintf('Expected a simple type, got non-simple type "%s".', TypeUtil::getDebugType($type)));
    }
}
