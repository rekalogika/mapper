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

namespace Rekalogika\Mapper\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Model\HashTable;
use Rekalogika\Mapper\Transformer\Trait\ArrayLikeTransformerTrait;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class TraversableToArrayAccessTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;
    use ArrayLikeTransformerTrait;

    public function __construct(
        private ArrayLikeMetadataFactoryInterface $arrayLikeMetadataFactory,
    ) {
    }

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // The source must be a Traversable or an array (a.k.a. iterable).

        if (!$source instanceof \Traversable && !is_array($source)) {
            throw new InvalidArgumentException(sprintf('Source must be instance of "\Traversable" or "array", "%s" given', get_debug_type($source)), context: $context);
        }

        // If the target is provided, make sure it is an array|ArrayAccess

        if ($target !== null && !$target instanceof \ArrayAccess && !is_array($target)) {
            throw new InvalidArgumentException(sprintf('If target is provided, it must be an instance of "\ArrayAccess" or "array", "%s" given', get_debug_type($target)), context: $context);
        }

        // create transformation metadata

        $targetMetadata = $this->arrayLikeMetadataFactory
            ->createArrayLikeMetadata($targetType);

        // If the target is not provided, instantiate it

        if ($target === null) {
            $target = $this->instantiateArrayAccessOrArray($targetMetadata, $context);
        }

        // Add the target to cache

        $context(ObjectCache::class)
            ->saveTarget($source, $targetType, $target, $context);

        // Transform source

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $transformed = $this->transformTraversableSource(
            source: $source,
            target: $target,
            targetMetadata: $targetMetadata,
            context: $context,
        );

        foreach ($transformed as $key => $value) {
            if ($key === null) {
                $target[] = $value;
            } else {
                $target[$key] = $value;
            }
        }

        return $target;
    }

    /**
     * @return \ArrayAccess|array
     * @phpstan-ignore-next-line
     */
    private function instantiateArrayAccessOrArray(
        ArrayLikeMetadata $targetMetadata,
        Context $context,
    ): \ArrayAccess|array {
        // if it wants an array, just return it. easy.

        if ($targetMetadata->isArray()) {
            return [];
        }

        // otherwise, we try to instantiate the target class

        $class = $targetMetadata->getClass();
        $reflectionClass = new \ReflectionClass($class);

        // if instantiable, instantiate

        if ($reflectionClass->isInstantiable()) {
            try {
                $result = $reflectionClass->newInstance();
            } catch (\ReflectionException) {
                throw new ClassNotInstantiableException($class, context: $context);
            }

            if (!$result instanceof \ArrayAccess) {
                throw new InvalidArgumentException(sprintf('Target class "%s" must implement "\ArrayAccess"', $class), context: $context);
            }

            return $result;
        }

        // at this point, $class must be an interface or an abstract class.
        // the following is a heuristic for some popular situations

        switch (true) {
            case $class === \ArrayAccess::class:
                if ($targetMetadata->memberKeyCanBeOtherThanIntOrString()) {
                    return new HashTable();
                }
                return new \ArrayObject();

            case $class === Collection::class:
            case $class === ReadableCollection::class:
                return new ArrayCollection();
        }

        throw new InvalidArgumentException(sprintf('We do not know how to create an instance of "%s"', $class));
    }

    public function getSupportedTransformation(): iterable
    {
        $sourceTypes = [
            TypeFactory::objectOfClass(\Traversable::class),
            TypeFactory::array(),
        ];

        $targetTypes = [
            TypeFactory::objectOfClass(Collection::class),
            TypeFactory::objectOfClass(ReadableCollection::class),
            TypeFactory::objectOfClass(ArrayCollection::class),
            TypeFactory::objectOfClass(\ArrayObject::class),
            TypeFactory::objectOfClass(\ArrayIterator::class),
            TypeFactory::objectOfClass(\ArrayAccess::class),
            TypeFactory::array(),
        ];

        foreach ($sourceTypes as $sourceType) {
            foreach ($targetTypes as $targetType) {
                yield new TypeMapping(
                    $sourceType,
                    $targetType,
                );
            }
        }
    }
}
