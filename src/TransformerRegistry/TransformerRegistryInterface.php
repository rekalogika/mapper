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

/**
 * @internal
 */
interface TransformerRegistryInterface
{
    public function get(string $id): TransformerInterface;

    /**
     * @param array<array-key,Type|MixedType> $sourceTypes
     * @param array<array-key,Type|MixedType> $targetTypes
     * @return SearchResult
     */
    public function findBySourceAndTargetTypes(
        array $sourceTypes,
        array $targetTypes,
    ): SearchResult;
}
