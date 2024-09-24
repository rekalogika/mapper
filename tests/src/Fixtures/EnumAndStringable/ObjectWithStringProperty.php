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

namespace Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable;

class ObjectWithStringProperty
{
    public ?string $backedEnum = null;

    public ?string $unitEnum = null;

    public static function preinitialized(): self
    {
        $instance = new self();
        $instance->backedEnum = 'foo';
        $instance->unitEnum = 'Foo';

        return $instance;
    }
}
