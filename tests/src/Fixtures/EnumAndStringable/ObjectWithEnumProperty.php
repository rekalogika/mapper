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

class ObjectWithEnumProperty
{
    public ?SomeBackedEnum $backedEnum = null;

    public ?SomeEnum $unitEnum = null;

    public static function preinitialized(): self
    {
        $instance = new self();
        $instance->backedEnum = SomeBackedEnum::Foo;
        $instance->unitEnum = SomeEnum::Foo;

        return $instance;
    }
}
