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
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final readonly class DynamicPropertiesDeterminer implements DynamicPropertiesDeterminerInterface
{
    public function allowsDynamicProperties(string $class): bool
    {
        return ClassUtil::allowsDynamicProperties($class);
    }
}
