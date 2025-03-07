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
use Rekalogika\Mapper\Debug\TraceableTransformer;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

final class TransformerReturnsUnexpectedValueException extends UnexpectedValueException
{
    public function __construct(
        mixed $source,
        Type|MixedType $targetType,
        mixed $target,
        TransformerInterface $transformer,
        Context $context,
    ) {
        if ($transformer instanceof TraceableTransformer) {
            $transformer = $transformer->getDecorated();
        }

        $message = \sprintf(
            'Trying to map source type "%s" to target type "%s", but the assigned transformer "%s" returns an unexpected type "%s".',
            get_debug_type($source),
            TypeUtil::getTypeString($targetType),
            $transformer::class,
            get_debug_type($target),
        );

        parent::__construct($message, context: $context);
    }
}
