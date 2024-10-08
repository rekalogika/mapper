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

final class ObjectWithInt implements DateTimeTestObjectInterface
{
    public ?int $property = null;

    #[\Override]
    public static function preinitialized(): static
    {
        $object = new self();
        $object->property = Constants::SOURCE_DATETIME_EPOCH;

        return $object;
    }

    #[\Override]
    public function getProperty(): mixed
    {
        return $this->property;
    }
}
