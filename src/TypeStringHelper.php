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

namespace Rekalogika\Mapper;

use Rekalogika\Mapper\Model\MixedType;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class TypeStringHelper
{
    /**
     * Example: If the variable type is
     * 'IteratorAggregate<int,IteratorAggregate<int,string>>', then this method
     * will return ['IteratorAggregate<int,IteratorAggregate<int,string>>',
     * 'IteratorAggregate<int,Traversable<int,string>>',
     * 'Traversable<int,IteratorAggregate<int,string>>',
     * 'Traversable<int,Traversable<int,string>>']
     *
     * Note: IteratorAggregate extends Traversable
     *
     * @param array<int,Type>|Type|MixedType $type
     * @return array<int,string>
     */
    public function getApplicableTypeStrings(array|Type|MixedType $type): array
    {
        if ($type instanceof MixedType) {
            $type = ['mixed'];
            return $type;
        }

        if ($type instanceof Type) {
            $type = [$type];
        }

        $typeStrings = [];

        foreach ($type as $type) {
            $typeStrings = array_merge($typeStrings, TypeUtil::getAllTypeStrings($type, true));
        }

        return $typeStrings;
    }
}
