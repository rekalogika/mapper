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

use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class PropertyPathMetadata
{
    /**
     * @param class-string $class
     * @param list<Type> $types
     * @param list<object> $attributes
     */
    public function __construct(
        private string $propertyPath,
        private string $class,
        private ?string $property,
        private array $types,
        private array $attributes,
    ) {}

    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * @return list<Type>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return list<object>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
