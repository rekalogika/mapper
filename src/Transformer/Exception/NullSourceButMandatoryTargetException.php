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
use Rekalogika\Mapper\Exception\RuntimeException;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class NullSourceButMandatoryTargetException extends RuntimeException
{
    public function __construct(
        ?Type $targetType,
        ?\Throwable $previous = null,
        ?Context $context = null,
    ) {
        parent::__construct(
            message: \sprintf(
                'The source is null, the target is mandatory & expected to be of type "%s". But no transformer is able to handle this case.',
                TypeUtil::getTypeString($targetType ?? TypeFactory::mixed()),
            ),
            previous: $previous,
            context: $context,
        );
    }
}
