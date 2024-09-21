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

namespace Rekalogika\Mapper\Tests\Fixtures\PropertyMapper;

use Rekalogika\Mapper\Attribute\AsPropertyMapper;

class PropertyMapperWithoutClassAttribute
{
    #[AsPropertyMapper(
        targetClass: SomeObjectDto::class,
        property: 'propertyA',
    )]
    public function mapPropertyA(SomeObject $object): string
    {
        return $object::class . '::propertyA';
    }
}
