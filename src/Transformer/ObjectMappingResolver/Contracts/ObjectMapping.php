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

final class ObjectMapping
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @param array<int,PropertyMapping> $propertyMapping
     * @param array<int,ConstructorMapping> $constructorMapping
     */
    public function __construct(
        private string $sourceClass,
        private string $targetClass,
        private array $propertyMapping,
        private array $constructorMapping,
        private bool $instantiable,
    ) {
    }

    /**
     * @return array<int,PropertyMapping>
     */
    public function getPropertyMapping(): array
    {
        return $this->propertyMapping;
    }

    /**
     * @return array<int,ConstructorMapping>
     */
    public function getConstructorMapping(): array
    {
        return $this->constructorMapping;
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
}
