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

namespace Rekalogika\Mapper\Transformer\Processor;

use Rekalogika\Mapper\Context\Context;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
interface ObjectProcessorInterface
{
    public function transform(
        object $source,
        ?object $target,
        Type $targetType,
        Context $context,
    ): object;
}
