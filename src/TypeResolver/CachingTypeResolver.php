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

use Rekalogika\Mapper\Contracts\MixedType;
use Symfony\Component\PropertyInfo\Type;

class CachingTypeResolver implements TypeResolverInterface
{
    public function __construct(
        private TypeResolverInterface $decorated,
    ) {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->typeStringCache = new \WeakMap();

        /** @psalm-suppress PropertyTypeCoercion */
        $this->simpleTypesCache = new \WeakMap();

        /** @psalm-suppress PropertyTypeCoercion */
        $this->isSimpleTypeCache = new \WeakMap();
    }

    // cheap. so we don't cache

    public function guessTypeFromVariable(mixed $variable): Type
    {
        return $this->decorated->guessTypeFromVariable($variable);
    }

    // can be expensive in a loop. we cache using a weakmap

    /**
     * @var \WeakMap<Type|MixedType,string>
     */
    private \WeakMap $typeStringCache;

    public function getTypeString(Type|MixedType $type): string
    {
        if ($result = $this->typeStringCache[$type] ?? null) {
            return $result;
        }

        $typeString = $this->decorated->getTypeString($type);
        $this->typeStringCache->offsetSet($type, $typeString);

        return $typeString;
    }

    // can be expensive in a loop. we cache using a weakmap

    /**
     * @var \WeakMap<Type,array<array-key,Type>>
     */
    private \WeakMap $simpleTypesCache;

    public function getSimpleTypes(Type $type): array
    {
        if ($result = $this->simpleTypesCache[$type] ?? null) {
            return $result;
        }

        $simpleTypes = $this->decorated->getSimpleTypes($type);
        $this->simpleTypesCache->offsetSet($type, $simpleTypes);

        return $simpleTypes;
    }

    // can be expensive in a loop. we cache using a weakmap

    /**
     * @var \WeakMap<Type,bool>
     */
    private \WeakMap $isSimpleTypeCache;

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

    public function getApplicableTypeStrings(Type|MixedType $type): array
    {
        $typeString = $this->getTypeString($type);

        if (isset($this->applicableTypeStringsCache[$typeString])) {
            return $this->applicableTypeStringsCache[$typeString];
        }

        $applicableTypeStrings = $this->decorated->getApplicableTypeStrings($type);
        $this->applicableTypeStringsCache[$typeString] = $applicableTypeStrings;

        return $applicableTypeStrings;
    }
}
