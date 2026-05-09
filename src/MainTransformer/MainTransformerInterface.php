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

namespace Rekalogika\Mapper\MainTransformer;

use Rekalogika\Mapper\Context\Context;
use Symfony\Component\TypeInfo\Type;

interface MainTransformerInterface
{
    /**
     * @param ?Type $sourceType If null, the source type will be guessed
     * @param ?Type $targetType The target type. May be a UnionType, NullableType
     *     or any other composite. If null and $target is null, the type
     *     defaults to mixed.
     */
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
        ?string $path = null,
    ): mixed;
}
