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

namespace Rekalogika\Mapper\Transformer\ArrayLikeMetadata;

use Rekalogika\Mapper\Exception\LogicException;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class ArrayLikeMetadata
{
    /**
     * @param ?class-string         $sourceClass
     * @param ?class-string         $targetClass
     * @param array<array-key,Type> $targetMemberKeyTypes
     * @param array<array-key,Type> $targetMemberValueTypes
     */
    public function __construct(
        private Type $sourceType,
        private Type $targetType,
        private bool $isSourceArray,
        private ?string $sourceClass,
        private bool $isTargetArray,
        private ?string $targetClass,
        private bool $targetCanBeLazy,
        private array $targetMemberKeyTypes,
        private array $targetMemberValueTypes,
        private bool $sourceMemberKeyCanBeInt,
        private bool $sourceMemberKeyCanBeString,
        private bool $sourceMemberKeyCanBeIntOnly,
        private bool $sourceMemberKeyCanBeOtherThanIntOrString,
        private bool $targetMemberKeyCanBeInt,
        private bool $targetMemberKeyCanBeString,
        private bool $targetMemberKeyCanBeIntOnly,
        private bool $targetMemberKeyCanBeOtherThanIntOrString,
        private bool $targetMemberValueIsUntyped,
    ) {}

    public function getSourceType(): Type
    {
        return $this->sourceType;
    }

    public function getTargetType(): Type
    {
        return $this->targetType;
    }

    /**
     * @return class-string
     */
    public function getSourceClass(): string
    {
        if (null === $this->sourceClass) {
            throw new LogicException('This method can only be called if the source is an array.');
        }

        return $this->sourceClass;
    }

    /**
     * @return class-string
     */
    public function getTargetClass(): string
    {
        if (null === $this->targetClass) {
            throw new LogicException('This method can only be called if the target is an array.');
        }

        return $this->targetClass;
    }

    /**
     * @return array<array-key,Type>
     */
    public function getTargetMemberKeyTypes(): array
    {
        return $this->targetMemberKeyTypes;
    }

    /**
     * @return array<array-key,Type>
     */
    public function getTargetMemberValueTypes(): array
    {
        return $this->targetMemberValueTypes;
    }

    public function targetMemberKeyCanBeInt(): bool
    {
        return $this->targetMemberKeyCanBeInt;
    }

    public function targetMemberKeyCanBeString(): bool
    {
        return $this->targetMemberKeyCanBeString;
    }

    public function targetMemberKeyCanBeIntOnly(): bool
    {
        return $this->targetMemberKeyCanBeIntOnly;
    }

    public function targetMemberKeyCanBeOtherThanIntOrString(): bool
    {
        return $this->targetMemberKeyCanBeOtherThanIntOrString;
    }

    public function targetMemberValueIsUntyped(): bool
    {
        return $this->targetMemberValueIsUntyped;
    }

    public function isIsSourceArray(): bool
    {
        return $this->isSourceArray;
    }

    public function isTargetArray(): bool
    {
        return $this->isTargetArray;
    }

    public function sourceMemberKeyCanBeInt(): bool
    {
        return $this->sourceMemberKeyCanBeInt;
    }

    public function sourceMemberKeyCanBeString(): bool
    {
        return $this->sourceMemberKeyCanBeString;
    }

    public function sourceMemberKeyCanBeIntOnly(): bool
    {
        return $this->sourceMemberKeyCanBeIntOnly;
    }

    public function sourceMemberKeyCanBeOtherThanIntOrString(): bool
    {
        return $this->sourceMemberKeyCanBeOtherThanIntOrString;
    }

    public function targetCanBeLazy(): bool
    {
        return $this->targetCanBeLazy;
    }
}
