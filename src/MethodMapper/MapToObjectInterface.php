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

namespace Rekalogika\Mapper\MethodMapper;

use Rekalogika\Mapper\MainTransformer\Context;

interface MapToObjectInterface
{
    public function mapToObject(
        object|string $target,
        SubMapperInterface $mapper,
        Context $context
    ): object;
}
