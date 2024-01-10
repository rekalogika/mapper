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

namespace Rekalogika\Mapper\Contracts;

use Symfony\Component\PropertyInfo\Type;

interface MainTransformerInterface
{
    /**
     * @param null|Type|array<array-key,Type> $targetType If provided, it will be used instead of guessing the type
     * @param array<string,mixed> $context
     */
    public function transform(
        mixed $source,
        mixed $target,
        null|Type|array $targetType,
        array $context
    ): mixed;
}
