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

namespace Rekalogika\Mapper\TypeResolver;

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Model\MixedType;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class TypeResolver implements TypeResolverInterface
{
    public function guessTypeFromVariable(mixed $variable): Type
    {
        if (is_object($variable)) {
            return TypeFactory::objectOfClass($variable::class);
        }

        if (is_null($variable)) {
            return TypeFactory::null();
        }

        if (is_array($variable)) {
            return TypeFactory::array();
        }

        if (is_bool($variable)) {
            return TypeFactory::bool();
        }

        if (is_int($variable)) {
            return TypeFactory::int();
        }

        if (is_float($variable)) {
            return TypeFactory::float();
        }

        if (is_string($variable)) {
            return TypeFactory::string();
        }

        if (is_resource($variable)) {
            return TypeFactory::resource();
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot determine type of variable "%s"',
            get_debug_type($variable),
        ));
    }

    public function getTypeString(Type|MixedType $type): string
    {
        return TypeUtil::getTypeString($type);
    }

    public function isSimpleType(Type $type): bool
    {
        return TypeUtil::isSimpleType($type);
    }

    /**
     * Gets all the possible simple types from a Type
     *
     * @param Type|array<array-key,Type> $type
     * @return array<array-key,Type>
     */
    public function getSimpleTypes(Type|array $type): array
    {
        return TypeUtil::getSimpleTypes($type);
    }

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
