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

namespace Rekalogika\Mapper\Tests\Fixtures\DateTime;

final class ObjectWithDateTimeImmutableGetterOnly implements DateTimeTestObjectInterface
{
    #[\Override]
    public static function preinitialized(): static
    {
        return new self();
    }

    #[\Override]
    public function getProperty(): mixed
    {
        return new \DateTimeImmutable('2024-01-01 13:00:00');
    }
}
