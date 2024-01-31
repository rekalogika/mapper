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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts;

use Rekalogika\Mapper\PropertyMapper\Contracts\PropertyMapperServicePointer;
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\PropertyInfo\Type;

final class PropertyMapping
{
    /**
     * @var array<int,Type> $sourceTypes
     */
    private array $sourceTypes;

    /**
     * @var array<int,Type> $targetTypes
     */
    private array $targetTypes;

    /**
     * @param array<array-key,Type> $sourceTypes
     * @param array<array-key,Type> $targetTypes
     * @param 'int'|'float'|'string'|'bool'|null $targetScalarType
     */
    public function __construct(
        private ?string $sourceProperty,
        private string $targetProperty,
        array $sourceTypes,
        array $targetTypes,
        private bool $readSource,
        private bool $initializeTarget,
        private bool $readTarget,
        private bool $writeTarget,
        private ?string $targetScalarType,
        private ?PropertyMapperServicePointer $propertyMapper
    ) {
        $this->sourceTypes = array_values($sourceTypes);
        $this->targetTypes = array_values($targetTypes);
    }

    public function getCompatibleSourceType(Type $type): ?Type
    {
        foreach ($this->sourceTypes as $sourceType) {
            if (TypeCheck::isSomewhatIdentical($sourceType, $type)) {
                return $sourceType;
            }
        }

        return null;
    }

    /**
     * Property path of the source
     */
    public function getSourceProperty(): ?string
    {
        return $this->sourceProperty;
    }

    public function setSourceProperty(?string $sourceProperty): void
    {
        $this->sourceProperty = $sourceProperty;
    }

    /**
     * Property path of the target
     */
    public function getTargetProperty(): string
    {
        return $this->targetProperty;
    }

    public function setTargetProperty(string $targetProperty): void
    {
        $this->targetProperty = $targetProperty;
    }

    /**
     * @return array<int,Type>
     */
    public function getSourceTypes(): array
    {
        return $this->sourceTypes;
    }

    /**
     * @param array<int,Type> $sourceTypes
     */
    public function setSourceTypes(array $sourceTypes): self
    {
        $this->sourceTypes = $sourceTypes;

        return $this;
    }

    /**
     * @return array<int,Type>
     */
    public function getTargetTypes(): array
    {
        return $this->targetTypes;
    }

    /**
     * @param array<int,Type> $targetTypes
     */
    public function setTargetTypes(array $targetTypes): void
    {
        $this->targetTypes = $targetTypes;
    }

    public function doInitializeTarget(): bool
    {
        return $this->initializeTarget;
    }

    public function setInitializeTarget(bool $initializeTarget): void
    {
        $this->initializeTarget = $initializeTarget;
    }

    public function doReadTarget(): bool
    {
        return $this->readTarget;
    }

    public function setReadTarget(bool $readTarget): void
    {
        $this->readTarget = $readTarget;
    }

    public function doWriteTarget(): bool
    {
        return $this->writeTarget;
    }

    public function setWriteTarget(bool $writeTarget): void
    {
        $this->writeTarget = $writeTarget;
    }

    public function doReadSource(): bool
    {
        return $this->readSource;
    }

    public function setReadSource(bool $readSource): void
    {
        $this->readSource = $readSource;
    }

    public function getPropertyMapper(): ?PropertyMapperServicePointer
    {
        return $this->propertyMapper;
    }

    public function setPropertyMapper(?PropertyMapperServicePointer $propertyMapper): void
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * If set, set the property directly, without delegating to the main
     * transformer
     *
     * @return 'int'|'float'|'string'|'bool'|null
     */
    public function getTargetScalarType(): ?string
    {
        return $this->targetScalarType;
    }

    /**
     * @param 'int'|'float'|'string'|'bool'|null $targetScalarType
     */
    public function setTargetScalarType(?string $targetScalarType): void
    {
        $this->targetScalarType = $targetScalarType;
    }
}
