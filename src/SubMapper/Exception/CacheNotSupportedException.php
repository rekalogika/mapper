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

namespace Rekalogika\Mapper\SubMapper\Exception;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\RuntimeException;

final class CacheNotSupportedException extends RuntimeException
{
    public function __construct(Context $context)
    {
        parent::__construct('The "cache()" method is not supported, and should be unnecessary in a sub-mapper under a property mapper.', context: $context);
    }
}
