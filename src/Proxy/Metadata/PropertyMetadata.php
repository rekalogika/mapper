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

namespace Rekalogika\Mapper\Proxy\Metadata;

/**
 * @internal
 */
final readonly class PropertyMetadata
{
    /**
     * @param class-string $class
     * @param class-string $scopeClass
     */
    public function __construct(
        private string $class,
        private string $scopeClass,
        private string $name,
        private string $scopeNotation,
        private bool $readOnly,
    ) {}

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return class-string
     */
    public function getScopeClass(): string
    {
        return $this->scopeClass;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getScopeNotation(): string
    {
        return $this->scopeNotation;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }
}
