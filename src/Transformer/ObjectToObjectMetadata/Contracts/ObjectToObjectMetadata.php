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

final class ObjectToObjectMetadata
{
    /**
     * @var array<int,PropertyMapping>
     */
    private array $propertyMappings = [];

    /**
     * @var array<int,ConstructorMapping>
     */
    private array $constructorMappings = [];

    private bool $instantiable = true;

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function __construct(
        private string $sourceClass,
        private string $targetClass,
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

    public function isInstantiable(): bool
    {
        return $this->instantiable;
    }

    public function setInstantiable(bool $instantiable): self
    {
        $this->instantiable = $instantiable;

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
     * @return array<int,ConstructorMapping>
     */
    public function getConstructorMappings(): array
    {
        return $this->constructorMappings;
    }

    public function addConstructorMapping(ConstructorMapping $constructorMapping): self
    {
        $this->constructorMappings[] = $constructorMapping;

        return $this;
    }

    public function removeConstructorMapping(ConstructorMapping $constructorMapping): self
    {
        $index = array_search($constructorMapping, $this->constructorMappings, true);

        if (false !== $index) {
            unset($this->constructorMappings[$index]);
            $this->constructorMappings = array_values($this->constructorMappings);
        }

        return $this;
    }

}
