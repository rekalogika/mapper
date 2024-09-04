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

namespace Rekalogika\Mapper\Tests\Fixtures\ArrayLike;

use Doctrine\Common\Collections\AbstractLazyCollection;

/**
 * @extends AbstractLazyCollection<array-key,mixed>
 */
class LazyDoctrineCollection extends AbstractLazyCollection
{
    #[\Override]
    protected function doInitialize()
    {
        throw new \LogicException("Not expected to be initialized");
    }
}
