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
use Rekalogika\Mapper\IterableMapperInterface;

final readonly class IterableMapperDecorator implements IterableMapperInterface
{
    public function __construct(
        private IterableMapperInterface $decorated,
        private Context $defaultContext
    ) {
    }

    public function mapIterable(iterable $source, string $target, ?Context $context = null): iterable
    {
        return $this->decorated->mapIterable($source, $target, $context ?? $this->defaultContext);
    }
}
