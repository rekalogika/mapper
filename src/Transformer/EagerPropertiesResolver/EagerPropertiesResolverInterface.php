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

namespace Rekalogika\Mapper\Transformer\EagerPropertiesResolver;

interface EagerPropertiesResolverInterface
{
    /**
     * Takes the source class name, and determine which properties that can be
     * read without causing a full hydration of the source. Usually, the
     * object's identifier is eager.
     *
     * @param class-string $sourceClass
     * @return list<string>
     */
    public function getEagerProperties(string $sourceClass): array;
}
