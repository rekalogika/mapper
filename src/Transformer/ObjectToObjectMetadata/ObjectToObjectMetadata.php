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

/**
 * @immutable
 */
final readonly class ObjectToObjectMetadata
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass Effective target class after resolving inheritance map
     * @param class-string $providedTargetClass
     * @param array<int,PropertyMapping> $propertyMappings
     * @param array<int,string> $initializableTargetPropertiesNotInSource
     */
    public function __construct(
        private string $sourceClass,
        private string $targetClass,
        private string $providedTargetClass,
        private array $propertyMappings = [],
        private bool $instantiable = true,
        private bool $cloneable = true,
        private array $initializableTargetPropertiesNotInSource = [],
        private int $sourceModifiedTime = 0,
        private int $targetModifiedTime = 0,
    ) {
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
}
