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

interface SubMapperInterface
{
    /**
     * Maps a source to the specified target.
     *
     * @template T of object
     * @param class-string<T>|T $target
     * @return T
     */
    public function map(
        object $source,
        object|string $target,
        ?Context $context
    ): object;

    /**
     * Maps a source to the type of the specified class & property
     *
     * @param class-string|object $containing
     */
    public function mapForProperty(
        object $source,
        object|string $containing,
        string $property,
        ?Context $context
    ): mixed;

    /**
     * Add the target to the object cache
     */
    public function cache(mixed $target): void;
}
