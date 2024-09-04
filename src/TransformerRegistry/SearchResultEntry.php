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

namespace Rekalogika\Mapper\TransformerRegistry;

use Rekalogika\Mapper\Transformer\MixedType;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class SearchResultEntry
{
    public function __construct(
        private int $mappingOrder,
        private MixedType|Type $sourceType,
        private MixedType|Type $targetType,
        private string $transformerServiceId,
        private bool $variantTargetType,
    ) {}

    public function getSourceType(): MixedType|Type
    {
        return $this->sourceType;
    }

    public function getTargetType(): MixedType|Type
    {
        return $this->targetType;
    }

    public function getMappingOrder(): int
    {
        return $this->mappingOrder;
    }

    public function isVariantTargetType(): bool
    {
        if ($this->targetType instanceof MixedType) {
            return true;
        }

        if (Type::BUILTIN_TYPE_OBJECT !== $this->targetType->getBuiltinType()) {
            return false;
        }

        return $this->variantTargetType;
    }

    public function getTransformerServiceId(): string
    {
        return $this->transformerServiceId;
    }
}
