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
     * @return Type|MixedType
     */
    public function getTargetType(): Type|MixedType
    {
        return $this->targetType;
    }
}
