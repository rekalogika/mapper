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

use Rekalogika\Mapper\Transformer\MixedType;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
interface TypeResolverInterface
{
    /**
     * Gets the string representation of a Type.
     */
    public function getTypeString(MixedType|Type $type): string;

    /**
     * Gets all the possible simple types from a Type.
     *
     * @param array<array-key,MixedType|Type>|MixedType|Type $type
     *
     * @return array<int,MixedType|Type>
     */
    public function getSimpleTypes(array|MixedType|Type $type): array;

    /**
     * Simple Type is a type that is not nullable, and does not have more
     * than one key type or value type.
     */
    public function isSimpleType(Type $type): bool;

    /**
     * Example: If the variable type is
     * 'IteratorAggregate<int,IteratorAggregate<int,string>>', then this method
     * will return ['IteratorAggregate<int,IteratorAggregate<int,string>>',
     * 'IteratorAggregate<int,Traversable<int,string>>',
     * 'Traversable<int,IteratorAggregate<int,string>>',
     * 'Traversable<int,Traversable<int,string>>'].
     *
     * Note: IteratorAggregate extends Traversable
     *
     * @return array<int,string>
     */
    public function getAcceptedTransformerInputTypeStrings(MixedType|Type $type): array;

    /**
     * Example: If the variable type is
     * 'IteratorAggregate<int,IteratorAggregate<int,string>>', then this method
     * will return ['IteratorAggregate<int,IteratorAggregate<int,string>>',
     * 'IteratorAggregate<int,Traversable<int,string>>',
     * 'Traversable<int,IteratorAggregate<int,string>>',
     * 'Traversable<int,Traversable<int,string>>'].
     *
     * Note: IteratorAggregate extends Traversable
     *
     * @return array<int,string>
     */
    public function getAcceptedTransformerOutputTypeStrings(MixedType|Type $type): array;
}
