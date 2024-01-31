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

namespace Rekalogika\Mapper\Transformer\Model;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\Exception\InvalidTypeInArgumentException;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class TraversableTransformerMetadata
{
    /**
     * @var array<array-key,Type>
     */
    private array $targetMemberKeyTypes;

    /**
     * @var array<array-key,Type>
     */
    private array $targetMemberValueTypes;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private bool $targetMemberKeyCanBeInt;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private bool $targetMemberKeyCanBeString;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private bool $targetMemberKeyCanBeIntOnly;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private bool $targetMemberKeyCanBeOtherThanIntOrString;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private bool $targetMemberValueIsUntyped;
    /** @psalm-suppress PropertyNotSetInConstructor */
    private bool $isTargetArray;

    public function __construct(
        private ?Type $sourceType,
        private Type $targetType,
        private Context $context,
    ) {
        // if the target member key type is not provided, we assume it is the
        // standard array-key

        $targetMemberKeyTypes = $targetType->getCollectionKeyTypes();

        if (count($targetMemberKeyTypes) === 0) {
            $targetMemberKeyTypes = [
                TypeFactory::int(),
                TypeFactory::string(),
            ];
        }

        $this->targetMemberKeyTypes = $targetMemberKeyTypes;
        $this->targetMemberValueTypes = $targetType->getCollectionValueTypes();
    }

    public function isTargetArray(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->isTargetArray)) {
            $this->isTargetArray = TypeCheck::isArray($this->targetType);
        }

        return $this->isTargetArray;
    }

    /**
     * @return class-string<\ArrayAccess<mixed,mixed>>
     */
    public function getArrayAccessTargetClass(): string
    {
        $class = $this->targetType->getClassName();

        if ($class === null) {
            throw new InvalidTypeInArgumentException('Target must be an instance of "\ArrayAccess" or "array, "%s" given', $class, context: $this->context);
        }

        if (!class_exists($class) && !\interface_exists($class)) {
            throw new InvalidArgumentException(sprintf('Target class "%s" does not exist', $class), context: $this->context);
        }

        if (!is_a($class, \ArrayAccess::class, true)) {
            throw new InvalidArgumentException(sprintf('Target class "%s" must implement "\ArrayAccess"', $class), context: $this->context);
        }

        return $class;
    }

    public function isValueCompatibleWithTargetTypes(mixed $value): bool
    {
        foreach ($this->targetMemberValueTypes as $targetMemberValueType) {
            if (TypeCheck::isVariableInstanceOf($value, $targetMemberValueType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * determine if the target member key type can be int or string
     */
    private function determineIntOrString(): void
    {
        $targetMemberKeyTypeCanBeInt = false;
        $targetMemberKeyTypeCanBeString = false;
        $targetMemberKeyTypeCanBeOtherThanIntOrString = false;

        foreach ($this->targetMemberKeyTypes as $targetMemberKeyType) {
            if (TypeCheck::isInt($targetMemberKeyType)) {
                $targetMemberKeyTypeCanBeInt = true;
            } elseif (TypeCheck::isString($targetMemberKeyType)) {
                $targetMemberKeyTypeCanBeString = true;
            } else {
                $targetMemberKeyTypeCanBeOtherThanIntOrString = true;
            }
        }

        $this->targetMemberKeyCanBeInt = $targetMemberKeyTypeCanBeInt;
        $this->targetMemberKeyCanBeString = $targetMemberKeyTypeCanBeString;
        $this->targetMemberKeyCanBeOtherThanIntOrString = $targetMemberKeyTypeCanBeOtherThanIntOrString;
    }

    public function targetMemberKeyCanBeInt(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->targetMemberKeyCanBeInt)) {
            $this->determineIntOrString();
        }

        return $this->targetMemberKeyCanBeInt;
    }

    public function targetMemberKeyCanBeString(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->targetMemberKeyCanBeString)) {
            $this->determineIntOrString();
        }

        return $this->targetMemberKeyCanBeString;
    }

    public function targetMemberKeyCanBeOtherThanIntOrString(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->targetMemberKeyCanBeOtherThanIntOrString)) {
            $this->determineIntOrString();
        }

        return $this->targetMemberKeyCanBeOtherThanIntOrString;
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

    public function targetMemberKeyCanBeIntOnly(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->targetMemberKeyCanBeIntOnly)) {
            $this->targetMemberKeyCanBeIntOnly =
                $this->targetMemberKeyCanBeInt()
                && !$this->targetMemberKeyCanBeString();
        }

        return $this->targetMemberKeyCanBeIntOnly;
    }

    public function targetMemberValueIsUntyped(): bool
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->targetMemberValueIsUntyped)) {
            $this->targetMemberValueIsUntyped =
                count($this->targetMemberValueTypes) === 0;
        }

        return $this->targetMemberValueIsUntyped;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getSourceType(): ?Type
    {
        return $this->sourceType;
    }

    public function getTargetType(): Type
    {
        return $this->targetType;
    }
}
