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

namespace Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts;

use Symfony\Component\PropertyInfo\Type;

final class ObjectMapping
{
    /**
     * @param array<int,ObjectMappingEntry> $propertyMapping
     */
    public function __construct(
        private Type $sourceType,
        private Type $targetType,
        private array $propertyMapping,
    ) {
    }

    public function getSourceType(): Type
    {
        return $this->sourceType;
    }

    public function getTargetType(): Type
    {
        return $this->targetType;
    }

    /**
     * @return \Traversable<int,ObjectMappingEntry>
     */
    public function getPropertyMapping(): \Traversable
    {
        yield from $this->propertyMapping;
    }
}
