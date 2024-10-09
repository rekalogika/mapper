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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\DynamicPropertiesDeterminer;

use Rekalogika\Mapper\Transformer\MetadataUtil\DynamicPropertiesDeterminerInterface;

/**
 * @internal
 */
final class CachingDynamicPropertiesDeterminer implements DynamicPropertiesDeterminerInterface
{
    /**
     * @var array<class-string,bool>
     */
    private array $cache = [];

    public function __construct(
        private readonly DynamicPropertiesDeterminerInterface $decorated,
    ) {}

    #[\Override]
    public function allowsDynamicProperties(string $class): bool
    {
        return $this->cache[$class]
            ??= $this->decorated->allowsDynamicProperties($class);
    }
}
