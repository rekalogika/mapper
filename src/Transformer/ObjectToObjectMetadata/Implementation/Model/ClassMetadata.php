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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Model;

/**
 * @internal
 */
final readonly class ClassMetadata
{
    /**
     * @param list<object> $attributes
     * @param list<string> $eagerProperties
     */
    public function __construct(
        private bool $internal,
        private bool $instantiable,
        private bool $cloneable,
        private bool $readonly,
        private bool $valueObject,
        private bool $readableDynamicProperties,
        private bool $writableDynamicProperties,
        private array $attributes,
        private array $eagerProperties,
        private int $lastModified,
    ) {}

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function isInstantiable(): bool
    {
        return $this->instantiable;
    }

    public function isCloneable(): bool
    {
        return $this->cloneable;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function isValueObject(): bool
    {
        return $this->valueObject;
    }

    public function hasReadableDynamicProperties(): bool
    {
        return $this->readableDynamicProperties;
    }

    public function hasWritableDynamicProperties(): bool
    {
        return $this->writableDynamicProperties;
    }

    /**
     * @return list<object>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return list<string>
     */
    public function getEagerProperties(): array
    {
        return $this->eagerProperties;
    }

    public function getLastModified(): int
    {
        return $this->lastModified;
    }
}
