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

namespace Rekalogika\Mapper;

use Rekalogika\Mapper\Context\Context;

interface MapperInterface
{
    /**
     * @template T of object
     * @param class-string<T>|T $target
     * @return T
     */
    public function map(mixed $source, object|string $target, ?Context $context = null): mixed;
}
