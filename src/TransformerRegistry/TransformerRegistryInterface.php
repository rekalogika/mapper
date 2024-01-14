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

namespace Rekalogika\Mapper\TransformerRegistry;

use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Symfony\Component\PropertyInfo\Type;

interface TransformerRegistryInterface
{
    public function get(string $id): TransformerInterface;

    /**
     * @param iterable<array-key,Type|MixedType> $sourceTypes
     * @param iterable<array-key,Type|MixedType> $targetTypes
     * @return SearchResult
     */
    public function findBySourceAndTargetTypes(
        iterable $sourceTypes,
        iterable $targetTypes,
    ): SearchResult;
}
