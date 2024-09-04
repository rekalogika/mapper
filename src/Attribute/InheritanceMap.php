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

namespace Rekalogika\Mapper\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class InheritanceMap
{
    /**
     * @param array<class-string,class-string> $map
     */
    public function __construct(
        private array $map = [],
    ) {}

    /**
     * @return array<class-string,class-string>
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @param class-string $sourceClass
     * @return class-string|null
     */
    public function getTargetClassFromSourceClass(string $sourceClass): ?string
    {
        return $this->map[$sourceClass] ?? null;
    }

    /**
     * @param class-string $targetClass
     * @return class-string|null
     */
    public function getSourceClassFromTargetClass(string $targetClass): ?string
    {
        return array_search($targetClass, $this->map, true) ?: null;
    }
}
