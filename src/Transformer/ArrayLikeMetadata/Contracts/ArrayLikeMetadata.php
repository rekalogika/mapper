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

namespace Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts;

use Rekalogika\Mapper\Exception\LogicException;
use Symfony\Component\PropertyInfo\Type;

final class ArrayLikeMetadata
{
    /**
     * @param ?class-string $class
     * @param array<array-key,Type> $memberKeyTypes
     * @param array<array-key,Type> $memberValueTypes
     */
    public function __construct(
        private Type $type,
        private bool $isArray,
        private ?string $class,
        private array $memberKeyTypes,
        private array $memberValueTypes,
        private bool $memberKeyCanBeInt,
        private bool $memberKeyCanBeString,
        private bool $memberKeyCanBeIntOnly,
        private bool $memberKeyCanBeOtherThanIntOrString,
        private bool $memberValueIsUntyped,
    ) {
    }

    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        if ($this->class === null) {
            throw new LogicException('This method can only be called if the target is an array.');
        }

        return $this->class;
    }

    /**
     * @return array<array-key,Type>
     */
    public function getMemberKeyTypes(): array
    {
        return $this->memberKeyTypes;
    }

    /**
     * @return array<array-key,Type>
     */
    public function getMemberValueTypes(): array
    {
        return $this->memberValueTypes;
    }

    public function memberKeyCanBeInt(): bool
    {
        return $this->memberKeyCanBeInt;
    }

    public function memberKeyCanBeString(): bool
    {
        return $this->memberKeyCanBeString;
    }

    public function memberKeyCanBeIntOnly(): bool
    {
        return $this->memberKeyCanBeIntOnly;
    }

    public function memberKeyCanBeOtherThanIntOrString(): bool
    {
        return $this->memberKeyCanBeOtherThanIntOrString;
    }

    public function memberValueIsUntyped(): bool
    {
        return $this->memberValueIsUntyped;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }
}
