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
 * @internal
 */
final readonly class TargetPropertyMetadata
{
    public function __construct(
        private ReadMode $readMode,
        private ?string $readName,
        private Visibility $readVisibility,
        private WriteMode $constructorWriteMode,
        private ?string $constructorWriteName,
        private WriteMode $setterWriteMode,
        private ?string $setterWriteName,
        private Visibility $setterWriteVisibility,
        private ?string $removerWriteName,
        private Visibility $removerWriteVisibility,
        private bool $allowsDelete,
    ) {}

    public function getReadMode(): ReadMode
    {
        return $this->readMode;
    }

    public function getReadName(): ?string
    {
        return $this->readName;
    }

    public function getReadVisibility(): Visibility
    {
        return $this->readVisibility;
    }

    public function getConstructorWriteMode(): WriteMode
    {
        return $this->constructorWriteMode;
    }

    public function getConstructorWriteName(): ?string
    {
        return $this->constructorWriteName;
    }

    public function getSetterWriteMode(): WriteMode
    {
        return $this->setterWriteMode;
    }

    public function getSetterWriteName(): ?string
    {
        return $this->setterWriteName;
    }

    public function getSetterWriteVisibility(): Visibility
    {
        return $this->setterWriteVisibility;
    }

    public function getRemoverWriteName(): ?string
    {
        return $this->removerWriteName;
    }

    public function getRemoverWriteVisibility(): Visibility
    {
        return $this->removerWriteVisibility;
    }

    public function allowsDelete(): bool
    {
        return $this->allowsDelete;
    }
}
