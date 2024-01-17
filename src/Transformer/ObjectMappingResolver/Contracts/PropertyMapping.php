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

use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\Type;

final class PropertyMapping
{
    /**
     * @var array<int,Type> $targetTypes
     */
    private array $targetTypes;

    /**
     * @param array<array-key,Type> $targetTypes
     */
    public function __construct(
        private string $sourceProperty,
        private string $targetProperty,
        array $targetTypes,
        private PropertyReadInfo $sourcePropertyReadInfo,
        private PropertyReadInfo $targetPropertyReadInfo,
        private PropertyWriteInfo $targetPropertyWriteInfo,
    ) {
        $this->targetTypes = array_values($targetTypes);
    }

    public function getSourceProperty(): string
    {
        return $this->sourceProperty;
    }

    public function getTargetProperty(): string
    {
        return $this->targetProperty;
    }

    /**
     * @return array<int,Type>
     */
    public function getTargetTypes(): array
    {
        return $this->targetTypes;
    }

    public function getSourcePropertyReadInfo(): PropertyReadInfo
    {
        return $this->sourcePropertyReadInfo;
    }

    public function getTargetPropertyReadInfo(): PropertyReadInfo
    {
        return $this->targetPropertyReadInfo;
    }

    public function getTargetPropertyWriteInfo(): PropertyWriteInfo
    {
        return $this->targetPropertyWriteInfo;
    }
}
