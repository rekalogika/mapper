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

namespace Rekalogika\Mapper\Transformer\MetadataUtil;

/**
 * @internal
 */
interface PropertyMappingResolverInterface
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @return list<array{?string,string}>
     */
    public function getPropertiesToMap(
        string $sourceClass,
        string $targetClass,
        bool $targetAllowsDynamicProperties,
    ): array;
}
