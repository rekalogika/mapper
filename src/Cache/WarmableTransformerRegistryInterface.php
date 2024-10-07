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

namespace Rekalogika\Mapper\Cache;

use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\TransformerRegistry\SearchResult;
use Symfony\Component\PropertyInfo\Type;

interface WarmableTransformerRegistryInterface
{
    /**
     * @param array<array-key,Type|MixedType> $sourceTypes
     * @param array<array-key,Type|MixedType> $targetTypes
     */
    public function warmFindBySourceAndTargetTypes(
        array $sourceTypes,
        array $targetTypes,
    ): ?SearchResult;
}
