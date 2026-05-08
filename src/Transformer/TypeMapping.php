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
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

final readonly class TypeMapping
{
    public function __construct(
        private Type $sourceType,
        private Type $targetType,
        private bool $variantTargetType = false,
    ) {
        if ($variantTargetType) {
            if (TypeCheck::isMixed($targetType)) {
                throw new InvalidArgumentException(
                    'Variant target type cannot be mixed',
                );
            }

            if (!$targetType->isIdentifiedBy(TypeIdentifier::OBJECT)) {
                throw new InvalidArgumentException(\sprintf(
                    'Variant target type must be object, %s given',
                    (string) $targetType,
                ));
            }
        }
    }

    public function getSourceType(): Type
    {
        return $this->sourceType;
    }

    public function getTargetType(): Type
    {
        return $this->targetType;
    }

    public function isVariantTargetType(): bool
    {
        return $this->variantTargetType;
    }
}
