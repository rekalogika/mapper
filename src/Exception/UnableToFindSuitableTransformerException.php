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

class UnableToFindSuitableTransformerException extends NotMappableValueException
{
    /**
     * @param Type $sourceType
     * @param Type|array<array-key,Type> $targetType
     */
    public function __construct(Type $sourceType, Type|array $targetType)
    {
        if (is_array($targetType)) {
            $targetType = implode(', ', array_map(fn (Type $type) => TypeUtil::getDebugType($type), $targetType));
        } else {
            $targetType = TypeUtil::getDebugType($targetType);
        }

        parent::__construct(sprintf('Unable to map the value "%s" to "%s"', TypeUtil::getDebugType($sourceType), $targetType));
    }
}
