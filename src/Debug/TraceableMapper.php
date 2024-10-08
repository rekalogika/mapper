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

namespace Rekalogika\Mapper\Debug;

use Rekalogika\Mapper\CacheWarmer\WarmableMapperInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Implementation\Mapper;
use Rekalogika\Mapper\IterableMapperInterface;
use Rekalogika\Mapper\MapperInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @internal
 */
final class TraceableMapper implements MapperInterface, IterableMapperInterface, WarmableMapperInterface
{
    public function __construct(
        private Mapper $decorated,
        private Stopwatch $stopwatch,
    ) {}

    /**
     * @template T of object
     * @param class-string<T>|T $target
     * @return T
     */
    public function map(
        object $source,
        object|string $target,
        ?Context $context = null,
    ): object {
        $this->stopwatch->start('map()', 'mapper');

        $result = $this->decorated->map($source, $target, $context);

        $this->stopwatch->stop('map()');

        return $result;
    }

    public function mapIterable(
        iterable $source,
        string $target,
        ?Context $context = null,
    ): iterable {
        $this->stopwatch->start('mapIterable()', 'mapper');

        $result = $this->decorated->mapIterable($source, $target, $context);

        $this->stopwatch->stop('mapIterable()');

        return $result;
    }

    public function warmingMap(string $sourceClass, string $targetClass): void
    {
        $this->decorated->warmingMap($sourceClass, $targetClass);
    }
}
