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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata;

use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;
use Rekalogika\Mapper\Transformer\Context\SourcePropertyAttributes;
use Rekalogika\Mapper\Transformer\Context\TargetPropertyAttributes;
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\PropertyInfo\Type;

/**
 * @immutable
 * @internal
 */
final readonly class PropertyMappingMetadata
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
     * @param 'int'|'float'|'string'|'bool'|'null'|null $targetScalarType
     */
    public function __construct(
        private string $id,
        private ?string $sourceProperty,
        private string $targetProperty,
        array $sourceTypes,
        array $targetTypes,
        private ReadMode $sourceReadMode,
        private ?string $sourceReadName,
        private Visibility $sourceReadVisibility,
        private ReadMode $targetReadMode,
        private ?string $targetReadName,
        private Visibility $targetReadVisibility,
        private WriteMode $targetSetterWriteMode,
        private ?string $targetSetterWriteName,
        private bool $targetSetterVariadic,
        private ?string $targetRemoverWriteName,
        private Visibility $targetSetterWriteVisibility,
        private Visibility $targetRemoverWriteVisibility,
        private WriteMode $targetConstructorWriteMode,
        private ?string $targetConstructorWriteName,
        private bool $targetConstructorMandatory,
        private bool $targetConstructorVariadic,
        private ?string $targetScalarType,
        private ?ServiceMethodSpecification $propertyMapper,
        private bool $sourceLazy,
        private bool $targetCanAcceptNull,
        private bool $targetReplaceable,
        private bool $hostCanMutateTarget,
        private bool $targetUnalterable,
        private SourcePropertyAttributes $sourceAttributes,
        private TargetPropertyAttributes $targetAttributes,
    ) {
        $this->sourceTypes = array_values($sourceTypes);
        $this->targetTypes = array_values($targetTypes);
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getSourceProperty(): ?string
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
    public function getSourceTypes(): array
    {
        return $this->sourceTypes;
    }

    /**
     * @return array<int,Type>
     */
    public function getTargetTypes(): array
    {
        return $this->targetTypes;
    }

    public function getPropertyMapper(): ?ServiceMethodSpecification
    {
        return $this->propertyMapper;
    }

    public function hasPropertyMapper(): bool
    {
        return $this->propertyMapper !== null;
    }

    /**
     * If set, set the property directly, without delegating to the main
     * transformer
     *
     * @return 'int'|'float'|'string'|'bool'|'null'|null
     */
    public function getTargetScalarType(): ?string
    {
        return $this->targetScalarType;
    }

    public function getSourceReadMode(): ReadMode
    {
        return $this->sourceReadMode;
    }

    public function getSourceReadName(): ?string
    {
        return $this->sourceReadName;
    }

    public function getTargetReadMode(): ReadMode
    {
        return $this->targetReadMode;
    }

    public function getTargetReadName(): ?string
    {
        return $this->targetReadName;
    }

    public function getTargetSetterWriteMode(): WriteMode
    {
        return $this->targetSetterWriteMode;
    }

    public function getTargetSetterWriteName(): ?string
    {
        return $this->targetSetterWriteName;
    }

    public function getTargetSetterWriteVisibility(): Visibility
    {
        return $this->targetSetterWriteVisibility;
    }

    public function isTargetSetterVariadic(): bool
    {
        return $this->targetSetterVariadic;
    }

    public function getTargetConstructorWriteMode(): WriteMode
    {
        return $this->targetConstructorWriteMode;
    }

    public function getTargetConstructorWriteName(): ?string
    {
        return $this->targetConstructorWriteName;
    }

    public function isTargetConstructorMandatory(): bool
    {
        return $this->targetConstructorMandatory;
    }

    public function isTargetConstructorVariadic(): bool
    {
        return $this->targetConstructorVariadic;
    }

    public function getSourceReadVisibility(): Visibility
    {
        return $this->sourceReadVisibility;
    }

    public function getTargetReadVisibility(): Visibility
    {
        return $this->targetReadVisibility;
    }

    public function isSourceLazy(): bool
    {
        return $this->sourceLazy;
    }

    public function targetCanAcceptNull(): bool
    {
        return $this->targetCanAcceptNull;
    }

    public function isTargetReplaceable(): bool
    {
        return $this->targetReplaceable;
    }

    public function isTargetUnalterable(): bool
    {
        return $this->targetUnalterable;
    }

    public function hostCanMutateTarget(): bool
    {
        return $this->hostCanMutateTarget;
    }

    public function getTargetRemoverWriteName(): ?string
    {
        return $this->targetRemoverWriteName;
    }

    public function getTargetRemoverWriteVisibility(): Visibility
    {
        return $this->targetRemoverWriteVisibility;
    }

    public function getSourceAttributes(): SourcePropertyAttributes
    {
        return $this->sourceAttributes;
    }

    public function getTargetAttributes(): TargetPropertyAttributes
    {
        return $this->targetAttributes;
    }
}
