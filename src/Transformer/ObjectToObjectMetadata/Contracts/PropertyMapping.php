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
        private ?string $sourceProperty,
        private string $targetProperty,
        array $targetTypes,
        private bool $readSource,
        private bool $initializeTarget,
        private bool $readTarget,
        private bool $writeTarget,
    ) {
        $this->targetTypes = array_values($targetTypes);
    }

    /**
     * Property path of the source
     */
    public function getSourceProperty(): ?string
    {
        return $this->sourceProperty;
    }

    public function setSourceProperty(?string $sourceProperty): self
    {
        $this->sourceProperty = $sourceProperty;

        return $this;
    }

    /**
     * Property path of the target
     */
    public function getTargetProperty(): string
    {
        return $this->targetProperty;
    }

    public function setTargetProperty(string $targetProperty): self
    {
        $this->targetProperty = $targetProperty;

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
    public function setTargetTypes(array $targetTypes): self
    {
        $this->targetTypes = $targetTypes;

        return $this;
    }

    public function doInitializeTarget(): bool
    {
        return $this->initializeTarget;
    }

    public function setInitializeTarget(bool $initializeTarget): self
    {
        $this->initializeTarget = $initializeTarget;

        return $this;
    }

    public function doReadTarget(): bool
    {
        return $this->readTarget;
    }

    public function setReadTarget(bool $readTarget): self
    {
        $this->readTarget = $readTarget;

        return $this;
    }

    public function doWriteTarget(): bool
    {
        return $this->writeTarget;
    }

    public function setWriteTarget(bool $writeTarget): self
    {
        $this->writeTarget = $writeTarget;

        return $this;
    }

    public function doReadSource(): bool
    {
        return $this->readSource;
    }

    public function setReadSource(bool $readSource): self
    {
        $this->readSource = $readSource;

        return $this;
    }
}
