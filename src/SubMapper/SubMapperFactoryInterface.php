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

namespace Rekalogika\Mapper\SubMapper;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;

/**
 * @internal
 */
interface SubMapperFactoryInterface
{
    public function createSubMapper(
        MainTransformerInterface $mainTransformer,
        Context $context
    ): SubMapperInterface;
}
