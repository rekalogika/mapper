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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\PropertyMetadataFactory;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;

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
        if (\count($types) !== 1) {
            return null;
        }

        $propertyType = $types[0];

        if (!$propertyType instanceof BuiltinType) {
            return null;
        }

        $identifier = $propertyType->getTypeIdentifier()->value;

        if (\in_array($identifier, ['int', 'float', 'string', 'bool', 'null'], true)) {
            /** @var 'int'|'float'|'string'|'bool'|'null' */
            return $identifier;
        }

        return null;
    }
}
