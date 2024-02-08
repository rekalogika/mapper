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

use Rekalogika\Mapper\Transformer\Proxy\ProxySpecification;

/**
 * @immutable
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
     * @param class-string $targetProxyClass
     * @param array<string,true> $targetProxySkippedProperties
     */
    public function __construct(
        private string $sourceClass,
        private string $targetClass,
        private string $providedTargetClass,
        array $allPropertyMappings,
        private bool $instantiable,
        private bool $cloneable,
        private array $initializableTargetPropertiesNotInSource,
        private int $sourceModifiedTime,
        private int $targetModifiedTime,
        private bool $targetReadOnly,
        private ?string $targetProxyClass = null,
        private ?string $targetProxyCode = null,
        private array $targetProxySkippedProperties = [],
        private ?string $cannotUseProxyReason = null,
    ) {
        $constructorPropertyMappings = [];
        $lazyPropertyMappings = [];
        $eagerPropertyMappings = [];
        $propertyPropertyMappings = [];

        foreach ($allPropertyMappings as $propertyMapping) {
            if ($propertyMapping->getTargetWriteMode() === WriteMode::Constructor) {
                $constructorPropertyMappings[] = $propertyMapping;
            } else {
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
        ProxySpecification $proxySpecification,
        array $targetProxySkippedProperties
    ): self {
        return new self(
            $this->sourceClass,
            $this->targetClass,
            $this->providedTargetClass,
            $this->allPropertyMappings,
            $this->instantiable,
            $this->cloneable,
            $this->initializableTargetPropertiesNotInSource,
            $this->sourceModifiedTime,
            $this->targetModifiedTime,
            $this->targetReadOnly,
            $proxySpecification->getClass(),
            $proxySpecification->getCode(),
            $targetProxySkippedProperties,
            cannotUseProxyReason: null
        );
    }

    public function withReasonCannotUseProxy(
        string $reason
    ): self {
        return new self(
            $this->sourceClass,
            $this->targetClass,
            $this->providedTargetClass,
            $this->allPropertyMappings,
            $this->instantiable,
            $this->cloneable,
            $this->initializableTargetPropertiesNotInSource,
            $this->sourceModifiedTime,
            $this->targetModifiedTime,
            $this->targetReadOnly,
            null,
            null,
            [],
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

    /**
     * @return class-string|null
     */
    public function getTargetProxyClass(): ?string
    {
        return $this->targetProxyClass;
    }

    public function getTargetProxyCode(): ?string
    {
        return $this->targetProxyCode;
    }

    public function getTargetProxySpecification(): ?ProxySpecification
    {
        if ($this->targetProxyClass === null || $this->targetProxyCode === null) {
            return null;
        }

        return new ProxySpecification(
            $this->targetProxyClass,
            $this->targetProxyCode
        );
    }

    public function canUseTargetProxy(): bool
    {
        return $this->targetProxyClass !== null && $this->targetProxyCode !== null;
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

    public function getCannotUseProxyReason(): ?string
    {
        return $this->cannotUseProxyReason;
    }
}
