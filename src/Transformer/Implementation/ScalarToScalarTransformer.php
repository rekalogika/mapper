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
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

final readonly class ScalarToScalarTransformer implements TransformerInterface
{
    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        if (!\is_scalar($source)) {
            throw new InvalidArgumentException(\sprintf('Source must be scalar, "%s" given.', get_debug_type($source)), context: $context);
        }

        $targetTypeIdentifier = $targetType instanceof BuiltinType
            ? $targetType->getTypeIdentifier()
            : null;

        return match ($targetTypeIdentifier) {
            TypeIdentifier::INT => (int) $source,
            TypeIdentifier::FLOAT => (float) $source,
            TypeIdentifier::STRING => (string) $source,
            TypeIdentifier::BOOL => (bool) $source,
            default => throw new InvalidArgumentException(\sprintf('Target must be scalar, "%s" given.', get_debug_type($targetType)), context: $context),
        };
    }

    #[\Override]
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
