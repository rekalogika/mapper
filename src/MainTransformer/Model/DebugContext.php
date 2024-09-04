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

namespace Rekalogika\Mapper\MainTransformer\Model;

use Rekalogika\Mapper\Transformer\MixedType;
use Symfony\Component\PropertyInfo\Type;

/**
 * Debug context for main transformer. Used for tracing.
 *
 * @internal
 * @immutable
 */
final readonly class DebugContext
{
    /**
     * @param array<int,Type|MixedType> $targetTypes
     */
    public function __construct(
        private Type $sourceType,
        private array $targetTypes,
        private bool $sourceTypeGuessed,
    ) {}

    public function getSourceType(): Type
    {
        return $this->sourceType;
    }

    /**
     * @return array<int,Type|MixedType>
     */
    public function getTargetTypes(): array
    {
        return $this->targetTypes;
    }

    public function isSourceTypeGuessed(): bool
    {
        return $this->sourceTypeGuessed;
    }
}
