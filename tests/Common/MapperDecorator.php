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

namespace Rekalogika\Mapper\Tests\Common;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MapperInterface;

final class MapperDecorator implements MapperInterface
{
    public function __construct(
        private MapperInterface $decorated,
        private Context $defaultContext
    ) {
    }

    /**
     * @template T of object
     * @param class-string<T>|T $target
     * @return T
     */
    public function map(
        object $source,
        object|string $target,
        ?Context $context = null
    ): object {
        return $this->decorated->map($source, $target, $context ?? $this->defaultContext);
    }
}
