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

namespace Rekalogika\Mapper\Tests\Fixtures\EnumAndStringableDto;

use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeBackedEnum;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeEnum;

class ObjectWithEnumStringablePropertyDto
{
    public ?string $stringable = null;

    public ?string $backedEnum = null;

    public ?string $unitEnum = null;

    public ?SomeBackedEnum $stringBackedEnum = null;

    public ?SomeEnum $stringUnitEnum = null;
}
