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

interface IterableMapperInterface
{
    /**
     * @template T of object
     *
     * @param iterable<mixed> $source
     * @param class-string<T> $target
     *
     * @return iterable<T>
     */
    public function mapIterable(iterable $source, string $target, ?Context $context = null): iterable;
}
