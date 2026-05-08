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

use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * @internal
 */
final readonly class SearchResultEntry
{
    public function __construct(
        private int $mappingOrder,
        private Type $sourceType,
        private Type $targetType,
        private string $transformerServiceId,
        private bool $variantTargetType,
    ) {}

    public function getSourceType(): Type
    {
        return $this->sourceType;
    }

    public function getTargetType(): Type
    {
        return $this->targetType;
    }

    public function getMappingOrder(): int
    {
        return $this->mappingOrder;
    }

    public function isVariantTargetType(): bool
    {
        if (TypeCheck::isMixed($this->targetType)) {
            return true;
        }

        if (!$this->targetType->isIdentifiedBy(TypeIdentifier::OBJECT)) {
            return false;
        }

        return $this->variantTargetType;
    }

    public function getTransformerServiceId(): string
    {
        return $this->transformerServiceId;
    }
}
