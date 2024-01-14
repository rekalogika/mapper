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
     * @param array<array-key,Type> $targetTypes If provided, it will be used instead of guessing the type
     */
    public function transform(
        mixed $source,
        mixed $target,
        array $targetTypes,
        Context $context,
        string $path = null,
    ): mixed;
}
