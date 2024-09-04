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

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Symfony\Component\PropertyInfo\Type;

final readonly class TypeMapping
{
    public function __construct(
        private MixedType|Type $sourceType,
        private MixedType|Type $targetType,
        private bool $variantTargetType = false,
    ) {
        if ($variantTargetType) {
            if ($targetType instanceof MixedType) {
                throw new InvalidArgumentException(
                    'Variant target type cannot be MixedType',
                );
            }

            if (Type::BUILTIN_TYPE_OBJECT !== $targetType->getBuiltinType()) {
                throw new InvalidArgumentException(sprintf(
                    'Variant target type must be object, %s given',
                    $targetType->getBuiltinType()
                ));
            }
        }
    }

    public function getSourceType(): MixedType|Type
    {
        return $this->sourceType;
    }

    public function getTargetType(): MixedType|Type
    {
        return $this->targetType;
    }

    public function isVariantTargetType(): bool
    {
        return $this->variantTargetType;
    }
}
