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

namespace Rekalogika\Mapper\Tests\Fixtures\InheritanceDto;

use Rekalogika\Mapper\Attribute\InheritanceMap;
use Rekalogika\Mapper\Tests\Fixtures\Inheritance\ConcreteClassA;
use Rekalogika\Mapper\Tests\Fixtures\Inheritance\ConcreteClassB;

#[InheritanceMap([
    ConcreteClassA::class => ConcreteClassADto::class,
    ConcreteClassB::class => ConcreteClassBDto::class,
    // C is deliberately omitted
])]
abstract class AbstractClassDto
{
    public ?string $propertyInParent = null;
}
