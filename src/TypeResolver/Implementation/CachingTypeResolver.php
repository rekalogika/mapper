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

namespace Rekalogika\Mapper\TypeResolver\Implementation;

use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class CachingTypeResolver implements TypeResolverInterface
{
    public function __construct(
        private readonly TypeResolverInterface $decorated,
    ) {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->typeStringCache = new \WeakMap();

        /** @psalm-suppress PropertyTypeCoercion */
        $this->isSimpleTypeCache = new \WeakMap();
    }

    // can be expensive in a loop. we cache using a weakmap

    /**
     * @var \WeakMap<Type|MixedType,string>
     */
    private \WeakMap $typeStringCache;

    #[\Override]
    public function getTypeString(Type|MixedType $type): string
    {
        $result = $this->typeStringCache[$type] ?? null;
        if ($result !== null) {
            return $result;
        }

        $typeString = $this->decorated->getTypeString($type);
        $this->typeStringCache->offsetSet($type, $typeString);

        return $typeString;
    }

    // can be expensive in a loop. we cache using a weakmap

    /**
     * @var array<string,array<int,MixedType|Type>>
     */
    private array $simpleTypesCache = [];

    #[\Override]
    public function getSimpleTypes(array|Type|MixedType $type): array
    {
        if ($type instanceof MixedType) {
            return [$type];
        }

        $key = hash('xxh128', serialize($type));

        $result = $this->simpleTypesCache[$key] ?? null;
        if ($result !== null) {
            return $result;
        }

        $simpleTypes = $this->decorated->getSimpleTypes($type);

        $this->simpleTypesCache[$key] = $simpleTypes;

        return $simpleTypes;
    }

    // can be expensive in a loop. we cache using a weakmap

    /**
     * @var \WeakMap<Type,bool>
     */
    private \WeakMap $isSimpleTypeCache;

    #[\Override]
    public function isSimpleType(Type $type): bool
    {
        if ($result = $this->isSimpleTypeCache[$type] ?? null) {
            return $result;
        }

        $isSimpleType = $this->decorated->isSimpleType($type);
        $this->isSimpleTypeCache->offsetSet($type, $isSimpleType);

        return $isSimpleType;
    }

    // expensive, but impossible to cache using a weakmap, so we use an array

    /**
     * @var array<string,array<int,string>>
     */
    private array $applicableTypeStringsCache = [];

    #[\Override]
    public function getAcceptedTransformerInputTypeStrings(Type|MixedType $type): array
    {
        $typeString = $this->getTypeString($type);

        if (isset($this->applicableTypeStringsCache[$typeString])) {
            return $this->applicableTypeStringsCache[$typeString];
        }

        $applicableTypeStrings = $this->decorated->getAcceptedTransformerInputTypeStrings($type);
        $this->applicableTypeStringsCache[$typeString] = $applicableTypeStrings;

        return $applicableTypeStrings;
    }

    #[\Override]
    public function getAcceptedTransformerOutputTypeStrings(Type|MixedType $type): array
    {
        return $this->decorated->getAcceptedTransformerOutputTypeStrings($type);
    }
}
