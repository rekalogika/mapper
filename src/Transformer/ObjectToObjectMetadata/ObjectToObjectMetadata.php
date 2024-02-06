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

final class ObjectToObjectMetadata
{
    /**
     * @var array<int,PropertyMapping>
     */
    private array $propertyMappings = [];

    private bool $instantiable = true;
    private bool $cloneable = true;

    /**
     * @var array<int,string>
     */
    private array $initializableTargetPropertiesNotInSource = [];

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass Effective target class after resolving inheritance map
     * @param class-string $providedTargetClass
     */
    public function __construct(
        private string $sourceClass,
        private string $targetClass,
        private string $providedTargetClass,
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

    public function setInstantiable(bool $instantiable): self
    {
        $this->instantiable = $instantiable;

        return $this;
    }

    public function isCloneable(): bool
    {
        return $this->cloneable;
    }

    public function setCloneable(bool $cloneable): self
    {
        $this->cloneable = $cloneable;

        return $this;
    }

    /**
     * @return array<int,PropertyMapping>
     */
    public function getPropertyMappings(): array
    {
        return $this->propertyMappings;
    }

    public function addPropertyMapping(PropertyMapping $propertyMapping): self
    {
        $this->propertyMappings[] = $propertyMapping;

        return $this;
    }

    public function removePropertyMapping(PropertyMapping $propertyMapping): self
    {
        $index = array_search($propertyMapping, $this->propertyMappings, true);

        if (false !== $index) {
            unset($this->propertyMappings[$index]);
            $this->propertyMappings = array_values($this->propertyMappings);
        }

        return $this;
    }

    /**
     * @return array<int,string>
     */
    public function getInitializableTargetPropertiesNotInSource(): array
    {
        return $this->initializableTargetPropertiesNotInSource;
    }

    /**
     * @param array<int,string> $initializableTargetPropertiesNotInSource
     */
    public function setInitializableTargetPropertiesNotInSource(array $initializableTargetPropertiesNotInSource): self
    {
        $this->initializableTargetPropertiesNotInSource = $initializableTargetPropertiesNotInSource;

        return $this;
    }
}
