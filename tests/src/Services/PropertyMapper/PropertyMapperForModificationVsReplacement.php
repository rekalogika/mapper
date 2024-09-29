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

namespace Rekalogika\Mapper\Tests\Services\PropertyMapper;

use Rekalogika\Mapper\Attribute\AsPropertyMapper;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\ChildObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\ObjectWithChild1;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\ObjectWithChild2;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObject;

class PropertyMapperForModificationVsReplacement
{
    #[AsPropertyMapper(
        targetClass: ObjectWithChild1::class,
        property: 'child',
    )]
    public function mapThatModifiesExistingTargetValue(
        SomeObject $source,
        ChildObject $currentTargetValue,
    ): ChildObject {
        $currentTargetValue->name = 'bar';

        return $currentTargetValue;
    }

    #[AsPropertyMapper(
        targetClass: ObjectWithChild2::class,
        property: 'child',
    )]
    public function mapThatReplacesExistingTargetValue(
        SomeObject $source,
        ChildObject $currentTargetValue,
    ): ChildObject {
        $currentTargetValue = new ChildObject();
        $currentTargetValue->name = 'bar';

        return $currentTargetValue;
    }
}
