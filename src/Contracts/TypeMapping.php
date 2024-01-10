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

namespace Rekalogika\Mapper\Contracts;

use Rekalogika\Mapper\Model\MixedType;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class TypeMapping
{
    /**
     * @param Type|MixedType $sourceType
     * @param Type|MixedType $targetType
     */
    public function __construct(
        private Type|MixedType $sourceType,
        private Type|MixedType $targetType,
    ) {
    }

    /**
     * @return Type|MixedType
     */
    public function getSourceType(): Type|MixedType
    {
        return $this->sourceType;
    }

    /**
     * @return array<array-key,Type|MixedType>
     */
    public function getSimpleSourceTypes(): array
    {
        if ($this->sourceType instanceof MixedType) {
            return [$this->sourceType];
        }

        return TypeUtil::getSimpleTypes($this->sourceType);
    }

    /**
     * @return Type|MixedType
     */
    public function getTargetType(): Type|MixedType
    {
        return $this->targetType;
    }

    /**
     * @return array<array-key,Type|MixedType>
     */
    public function getSimpleTargetTypes(): array
    {
        if ($this->targetType instanceof MixedType) {
            return [$this->targetType];
        }

        return TypeUtil::getSimpleTypes($this->targetType);
    }
}
