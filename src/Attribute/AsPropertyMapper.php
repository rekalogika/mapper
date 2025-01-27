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

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class AsPropertyMapper
{
    /**
     * @param class-string|null $targetClass
     */
    public function __construct(
        public ?string $property = null,
        public ?string $targetClass = null,
        public bool $ignoreUninitialized = false,
    ) {}
}
