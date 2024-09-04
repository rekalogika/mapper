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

/**
 * @template TKey of int
 * @template TValue
 * @extends \IteratorAggregate<TKey,TValue>
 * @extends \ArrayAccess<TKey,TValue>
 */
interface ListInterface extends \ArrayAccess, \IteratorAggregate, \Countable {}
