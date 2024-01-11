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

class MissingMemberKeyTypeException extends MissingMemberTypeException
{
    public function __construct(Type $sourceType, Type $targetType)
    {
        parent::__construct(sprintf('Trying to map collection type "%s" to "%s", but the source member key is not the simple array-key type, and the target does not have the type information about the key of its child members. Usually you can fix this by adding a PHPdoc to the property containing the collection type.', TypeUtil::getDebugType($sourceType), TypeUtil::getDebugType($targetType)));
    }
}
