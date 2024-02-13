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

namespace Rekalogika\Mapper\Mapping;

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class MappingEntry
{
    private static int $counter = 0;
    private int $order;

    public function __construct(
        private string $id,
        private string $class,
        private Type|MixedType $sourceType,
        private Type|MixedType $targetType,
        private string $sourceTypeString,
        private string $targetTypeString,
        private bool $variantTargetType,
    ) {
        $this->order = ++self::$counter;

        if ($variantTargetType === true) {
            if ($targetType instanceof MixedType) {
                throw new InvalidArgumentException(
                    'Variant target type cannot be MixedType',
                );
            }

            if ($targetType->getBuiltinType() !== Type::BUILTIN_TYPE_OBJECT) {
                throw new InvalidArgumentException(sprintf(
                    'Variant target type must be object, %s given',
                    $targetType->getBuiltinType()
                ));
            }
        }
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function isVariantTargetType(): bool
    {
        if ($this->targetType instanceof MixedType) {
            return true;
        }

        if ($this->targetType->getBuiltinType() !== Type::BUILTIN_TYPE_OBJECT) {
            return false;
        }

        return $this->variantTargetType;
    }

    public function getSourceType(): Type|MixedType
    {
        return $this->sourceType;
    }

    public function getTargetType(): Type|MixedType
    {
        return $this->targetType;
    }

    public function getSourceTypeString(): string
    {
        return $this->sourceTypeString;
    }

    public function getTargetTypeString(): string
    {
        return $this->targetTypeString;
    }
}
