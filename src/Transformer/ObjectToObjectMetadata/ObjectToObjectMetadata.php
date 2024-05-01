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

use Rekalogika\Mapper\Proxy\ProxyNamer;

/**
 * @immutable
 * @internal
 */
final readonly class ObjectToObjectMetadata
{
    /**
     * @var array<int,PropertyMapping>
     */
    private array $allPropertyMappings;

    /**
     * @var array<int,PropertyMapping>
     */
    private array $propertyMappings;

    /**
     * @var array<int,PropertyMapping>
     */
    private array $constructorPropertyMappings;

    /**
     * @var array<int,PropertyMapping>
     */
    private array $lazyPropertyMappings;

    /**
     * @var array<int,PropertyMapping>
     */
    private array $eagerPropertyMappings;

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass Effective target class after resolving inheritance map
     * @param class-string $providedTargetClass
     * @param array<int,PropertyMapping> $allPropertyMappings
     * @param array<int,string> $initializableTargetPropertiesNotInSource
     * @param array<string,true> $targetProxySkippedProperties
     * @param array<int,string> $sourceProperties List of the source properties. Used by `ObjectToObjectTransformer` to determine if a property is a dynamic property. A property not listed here is considered dynamic.
     */
    public function __construct(
        private string $sourceClass,
        private string $targetClass,
        private string $providedTargetClass,
        private bool $sourceAllowsDynamicProperties,
        private bool $targetAllowsDynamicProperties,
        private array $sourceProperties,
        array $allPropertyMappings,
        private bool $instantiable,
        private bool $cloneable,
        private array $initializableTargetPropertiesNotInSource,
        private int $sourceModifiedTime,
        private int $targetModifiedTime,
        private bool $targetReadOnly,
        private bool $constructorIsEager,
        private array $targetProxySkippedProperties = [],
        private ?string $cannotUseProxyReason = null,
    ) {
        $constructorPropertyMappings = [];
        $lazyPropertyMappings = [];
        $eagerPropertyMappings = [];
        $propertyPropertyMappings = [];

        foreach ($allPropertyMappings as $propertyMapping) {
            if ($propertyMapping->getTargetConstructorWriteMode() === WriteMode::Constructor) {
                $constructorPropertyMappings[] = $propertyMapping;
            }

            if ($propertyMapping->getTargetSetterWriteMode() !== WriteMode::None) {
                $propertyPropertyMappings[] = $propertyMapping;

                if ($propertyMapping->isSourceLazy()) {
                    $lazyPropertyMappings[] = $propertyMapping;
                } else {
                    $eagerPropertyMappings[] = $propertyMapping;
                }
            }
        }

        $this->constructorPropertyMappings = $constructorPropertyMappings;
        $this->lazyPropertyMappings = $lazyPropertyMappings;
        $this->eagerPropertyMappings = $eagerPropertyMappings;
        $this->propertyMappings = $propertyPropertyMappings;
        $this->allPropertyMappings = $allPropertyMappings;
    }

    /**
     * @param array<string,true> $targetProxySkippedProperties
     * @return self
     */
    public function withTargetProxy(
        array $targetProxySkippedProperties,
        bool $constructorIsEager,
    ): self {
        return new self(
            sourceClass: $this->sourceClass,
            targetClass: $this->targetClass,
            providedTargetClass: $this->providedTargetClass,
            sourceAllowsDynamicProperties: $this->sourceAllowsDynamicProperties,
            targetAllowsDynamicProperties: $this->targetAllowsDynamicProperties,
            sourceProperties: $this->sourceProperties,
            allPropertyMappings: $this->allPropertyMappings,
            instantiable: $this->instantiable,
            cloneable: $this->cloneable,
            initializableTargetPropertiesNotInSource: $this->initializableTargetPropertiesNotInSource,
            sourceModifiedTime: $this->sourceModifiedTime,
            targetModifiedTime: $this->targetModifiedTime,
            targetReadOnly: $this->targetReadOnly,
            constructorIsEager: $constructorIsEager,
            targetProxySkippedProperties: $targetProxySkippedProperties,
            cannotUseProxyReason: null
        );
    }

    public function withReasonCannotUseProxy(
        string $reason
    ): self {
        return new self(
            sourceClass: $this->sourceClass,
            targetClass: $this->targetClass,
            providedTargetClass: $this->providedTargetClass,
            sourceAllowsDynamicProperties: $this->sourceAllowsDynamicProperties,
            targetAllowsDynamicProperties: $this->targetAllowsDynamicProperties,
            sourceProperties: $this->sourceProperties,
            allPropertyMappings: $this->allPropertyMappings,
            instantiable: $this->instantiable,
            cloneable: $this->cloneable,
            initializableTargetPropertiesNotInSource: $this->initializableTargetPropertiesNotInSource,
            sourceModifiedTime: $this->sourceModifiedTime,
            targetModifiedTime: $this->targetModifiedTime,
            targetReadOnly: $this->targetReadOnly,
            constructorIsEager: $this->constructorIsEager,
            targetProxySkippedProperties: [],
            cannotUseProxyReason: $reason,
        );
    }

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    /**
     * @return class-string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    /**
     * @return class-string
     */
    public function getProvidedTargetClass(): string
    {
        return $this->providedTargetClass;
    }

    public function isInstantiable(): bool
    {
        return $this->instantiable;
    }

    public function isCloneable(): bool
    {
        return $this->cloneable;
    }

    /**
     * @return array<int,PropertyMapping>
     */
    public function getPropertyMappings(): array
    {
        return $this->propertyMappings;
    }

    /**
     * @return array<int,PropertyMapping>
     */
    public function getLazyPropertyMappings(): array
    {
        return $this->lazyPropertyMappings;
    }

    /**
     * @return array<int,PropertyMapping>
     */
    public function getEagerPropertyMappings(): array
    {
        return $this->eagerPropertyMappings;
    }

    /**
     * @return array<int,PropertyMapping>
     */
    public function getConstructorPropertyMappings(): array
    {
        return $this->constructorPropertyMappings;
    }

    /**
     * @return array<int,PropertyMapping>
     */
    public function getAllPropertyMappings(): array
    {
        return $this->allPropertyMappings;
    }

    /**
     * @return array<int,string>
     */
    public function getInitializableTargetPropertiesNotInSource(): array
    {
        return $this->initializableTargetPropertiesNotInSource;
    }

    public function getSourceModifiedTime(): int
    {
        return $this->sourceModifiedTime;
    }

    public function getTargetModifiedTime(): int
    {
        return $this->targetModifiedTime;
    }

    public function getModifiedTime(): int
    {
        return max($this->sourceModifiedTime, $this->targetModifiedTime);
    }

    public function getCannotUseProxyReason(): ?string
    {
        return $this->cannotUseProxyReason;
    }

    /**
     * @return class-string|null
     */
    public function getTargetProxyClass(): ?string
    {
        if ($this->cannotUseProxyReason !== null) {
            return null;
        }

        /** @var class-string */
        return ProxyNamer::generateProxyClassName($this->targetClass);
    }

    public function canUseTargetProxy(): bool
    {
        return $this->cannotUseProxyReason === null;
    }

    /**
     * @return array<string,true>
     */
    public function getTargetProxySkippedProperties(): array
    {
        return $this->targetProxySkippedProperties;
    }

    public function isTargetReadOnly(): bool
    {
        return $this->targetReadOnly;
    }

    public function constructorIsEager(): bool
    {
        return $this->constructorIsEager;
    }

    public function sourceAllowsDynamicProperties(): bool
    {
        return $this->sourceAllowsDynamicProperties;
    }

    public function targetAllowsDynamicProperties(): bool
    {
        return $this->targetAllowsDynamicProperties;
    }

    /**
     * @return array<int,string>
     */
    public function getSourceProperties(): array
    {
        return $this->sourceProperties;
    }
}
