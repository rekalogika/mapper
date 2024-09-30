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

namespace Rekalogika\Mapper\Transformer\MetadataUtil;

use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class Util
{
    private function __construct() {}

    /**
     * @param list<Type> $types
     * @return 'int'|'float'|'string'|'bool'|'null'|null
     */
    public static function determineScalarType(array $types): ?string
    {
        /** @var 'int'|'float'|'string'|'bool'|'null'|null */
        $scalarType = null;

        if (\count($types) === 1) {
            $propertyType = $types[0];
            $propertyBuiltInType = $propertyType->getBuiltinType();

            if (\in_array(
                $propertyBuiltInType,
                ['int', 'float', 'string', 'bool', 'null'],
                true,
            )) {
                $scalarType = $propertyBuiltInType;
            }
        }

        return $scalarType;
    }
}
