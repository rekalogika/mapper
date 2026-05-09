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
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\TypeInfo\Type;

final class CannotFindTransformerException extends UnexpectedValueException
{
    /**
     * @param array<int,Type> $sourceTypes
     * @param array<int,Type> $targetTypes
     */
    public function __construct(array $sourceTypes, array $targetTypes, Context $context)
    {
        $sourceTypes = TypeUtil::getDebugType($sourceTypes);
        $targetTypes = TypeUtil::getDebugType($targetTypes);

        parent::__construct(
            \sprintf('Cannot find a matching transformer for mapping the source types "%s" to the target types "%s".', $sourceTypes, $targetTypes),
            context: $context,
        );
    }
}
