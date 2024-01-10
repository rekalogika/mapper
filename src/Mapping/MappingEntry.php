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

final class MappingEntry
{
    private static int $counter = 0;
    private int $order;

    public function __construct(
        private string $id,
        private string $class,
        private string $sourceType,
        private string $targetType,
    ) {
        $this->order = ++self::$counter;
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

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }
}
