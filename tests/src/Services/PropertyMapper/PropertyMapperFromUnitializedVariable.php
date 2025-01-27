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
use Rekalogika\Mapper\Exception\RefuseToMapException;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectWithUninitializedVariable;
use Rekalogika\Mapper\Tests\Fixtures\PropertyMapper\SomeObjectWithUninitializedVariableDto;

#[AsPropertyMapper(
    targetClass: SomeObjectWithUninitializedVariableDto::class,
)]
class PropertyMapperFromUnitializedVariable
{
    #[AsPropertyMapper('propertyA')]
    public function mapPropertyA(SomeObjectWithUninitializedVariable $object): string
    {
        try {
            return $object->propertyA;
            // @phpstan-ignore catch.neverThrown
        } catch (\Error $e) {
            if (str_contains($e->getMessage(), 'must not be accessed before initialization')) {
                throw new RefuseToMapException();
            }

            throw $e;
        }
    }
}
