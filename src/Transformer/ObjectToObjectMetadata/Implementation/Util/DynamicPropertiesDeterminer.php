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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util;

use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final class DynamicPropertiesDeterminer
{
    /**
     * @var array<class-string,bool>
     */
    private array $cache = [];

    /**
     * @param class-string $class
     */
    public function allowsDynamicProperties(string $class): bool
    {
        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }

        return $this->cache[$class] = ClassUtil::allowsDynamicProperties($class);
    }
}
