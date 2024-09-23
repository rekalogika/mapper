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

use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class SourcePropertyMetadata
{
    /**
     * @param list<Type> $types
     */
    public function __construct(
        private ReadMode $readMode,
        private ?string $readName,
        private Visibility $readVisibility,
        private bool $allowsTargetDelete,
        private array $types,
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

    public function allowsTargetDelete(): bool
    {
        return $this->allowsTargetDelete;
    }

    /**
     * @return list<Type>
     */
    public function getTypes(): array
    {
        return $this->types;
    }
}
