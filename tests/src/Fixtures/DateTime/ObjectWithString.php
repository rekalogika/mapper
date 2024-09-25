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

final class ObjectWithString implements DateTimeTestObjectInterface
{
    public ?string $property = null;

    public static function preinitialized(): static
    {
        $object = new static();
        $object->property = Constants::SOURCE_DATETIME;

        return $object;
    }

    public function getProperty(): mixed
    {
        return $this->property;
    }
}