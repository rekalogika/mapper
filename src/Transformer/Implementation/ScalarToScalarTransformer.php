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

namespace Rekalogika\Mapper\Transformer\Implementation;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final readonly class ScalarToScalarTransformer implements TransformerInterface
{
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if (!is_scalar($source)) {
            throw new InvalidArgumentException(sprintf('Source must be scalar, "%s" given.', get_debug_type($source)), context: $context);
        }

        $targetTypeBuiltIn = $targetType?->getBuiltinType();

        switch ($targetTypeBuiltIn) {
            case Type::BUILTIN_TYPE_INT:
                return (int) $source;
            case Type::BUILTIN_TYPE_FLOAT:
                return (float) $source;
            case Type::BUILTIN_TYPE_STRING:
                return (string) $source;
            case Type::BUILTIN_TYPE_BOOL:
                return (bool) $source;
        }

        throw new InvalidArgumentException(sprintf('Target must be scalar, "%s" given.', get_debug_type($targetType)), context: $context);
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
