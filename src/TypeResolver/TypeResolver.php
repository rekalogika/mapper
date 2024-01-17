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
use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;

class TypeResolver implements TypeResolverInterface
{
    public function guessTypeFromVariable(mixed $variable): Type
    {
        $type = get_debug_type($variable);

        if (in_array($type, ['array', 'bool', 'int', 'float', 'string', 'null'])) {
            return new Type($type);
        }

        if (class_exists($type) || interface_exists($type) || \enum_exists($type)) {
            return new Type(
                builtinType: 'object',
                class: $type,
            );
        }

        if (\str_starts_with($type, 'resource')) {
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

    public function getSimpleTypes(Type|MixedType $type): array
    {
        if ($type instanceof MixedType) {
            return [$type];
        }

        return TypeUtil::getSimpleTypes($type);
    }

    public function getAcceptedTransformerInputTypeStrings(Type|MixedType $type): array
    {
        if ($type instanceof MixedType) {
            $type = ['mixed'];
            return $type;
        }

        return array_merge(
            TypeUtil::getAllTypeStrings($type, true),
            TypeUtil::getAttributesTypeStrings($type)
        );
    }


    public function getAcceptedTransformerOutputTypeStrings(Type|MixedType $type): array
    {
        return $this->getAcceptedTransformerInputTypeStrings($type);
    }
}
