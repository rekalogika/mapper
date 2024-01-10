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

namespace Rekalogika\Mapper\Transformer;

use Rekalogika\Mapper\Contracts\TransformerInterface;
use Rekalogika\Mapper\Contracts\TypeMapping;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class ScalarToScalarTransformer implements TransformerInterface
{
    public function transform(
        mixed $source,
        mixed $target,
        Type $sourceType,
        Type $targetType,
        array $context
    ): mixed {
        if (!is_scalar($source)) {
            throw new InvalidArgumentException(sprintf('Source must be scalar, "%s" given', get_debug_type($source)));
        }

        if (TypeCheck::isInt($targetType)) {
            return (int) $source;
        }

        if (TypeCheck::isFloat($targetType)) {
            return (float) $source;
        }

        if (TypeCheck::isString($targetType)) {
            return (string) $source;
        }

        if (TypeCheck::isBool($targetType)) {
            return (bool) $source;
        }

        throw new InvalidArgumentException(sprintf('Target must be scalar, "%s" given', get_debug_type($targetType)));
    }

    public function getSupportedTransformation(): iterable
    {
        $types = [
            TypeFactory::int(),
            TypeFactory::float(),
            TypeFactory::string(),
            TypeFactory::bool(),
        ];

        foreach ($types as $type1) {
            foreach ($types as $type2) {
                yield new TypeMapping($type1, $type2);
            }
        }
    }
}
