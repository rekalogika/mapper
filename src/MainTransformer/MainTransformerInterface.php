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
use Symfony\Component\PropertyInfo\Type;

interface MainTransformerInterface
{
    /**
     * @param ?Type $sourceType If null, the source type will be guessed
     * @param array<array-key,Type> $targetTypes
     */
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        array $targetTypes,
        Context $context,
        ?string $path = null,
    ): mixed;
}
