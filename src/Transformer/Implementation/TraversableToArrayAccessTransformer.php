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

namespace Rekalogika\Mapper\Transformer\Implementation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Rekalogika\Mapper\Attribute\AllowDelete;
use Rekalogika\Mapper\Attribute\AllowTargetDelete;
use Rekalogika\Mapper\CollectionInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\Context\SourceAttributes;
use Rekalogika\Mapper\Transformer\Context\TargetAttributes;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Model\HashTable;
use Rekalogika\Mapper\Transformer\Model\LazyArray;
use Rekalogika\Mapper\Transformer\Trait\ArrayLikeTransformerTrait;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\PropertyInfo\Type;

final class TraversableToArrayAccessTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;
    use ArrayLikeTransformerTrait;

    public function __construct(
        private ArrayLikeMetadataFactoryInterface $arrayLikeMetadataFactory,
    ) {}

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        if ($source === null) {
            $source = [];
        }

        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // The source must be a Traversable or an array (a.k.a. iterable).

        if (!$source instanceof \Traversable && !\is_array($source)) {
            throw new InvalidArgumentException(\sprintf('Source must be instance of "\Traversable" or "array", "%s" given', get_debug_type($source)), context: $context);
        }

        // If the target is provided, make sure it is an array|ArrayAccess

        if ($target !== null && !$target instanceof \ArrayAccess && !\is_array($target)) {
            throw new InvalidArgumentException(\sprintf('If target is provided, it must be an instance of "\ArrayAccess" or "array", "%s" given', get_debug_type($target)), context: $context);
        }

        // create transformation metadata

        if ($sourceType === null) {
            $sourceType = TypeGuesser::guessTypeFromVariable($source);
        }

        $metadata = $this->arrayLikeMetadataFactory
            ->createArrayLikeMetadata($sourceType, $targetType);

        // Transform source

        if (
            $metadata->targetCanBeLazy()
            && $target === null
            && (
                \is_array($source) || (
                    $source instanceof \ArrayAccess
                    && $source instanceof \Countable
                )
            )
        ) {
            /** @psalm-suppress PossiblyInvalidArgument */
            return $this->lazyTransform(
                source: $source,
                metadata: $metadata,
                context: $context,
            );
        }

        return $this->eagerTransform(
            source: $source,
            target: $target,
            metadata: $metadata,
            context: $context,
        );
    }

    /**
     * @param (\Traversable<array-key,mixed>&\ArrayAccess<array-key,mixed>&\Countable)|array<array-key,mixed> $source
     */
    private function lazyTransform(
        (\Traversable&\ArrayAccess&\Countable)|array $source,
        ArrayLikeMetadata $metadata,
        Context $context,
    ): mixed {
        return new LazyArray(
            source: $source,
            metadata: $metadata,
            context: $context,
            mainTransformer: $this->getMainTransformer(),
        );
    }

    /**
     * @param iterable<mixed,mixed> $source
     * @param \ArrayAccess<mixed,mixed>|array<array-key,mixed> $target
     */
    private function eagerTransform(
        iterable $source,
        \ArrayAccess|array|null $target,
        ArrayLikeMetadata $metadata,
        Context $context,
    ): mixed {
        // If the target is not provided, instantiate it

        if ($target === null) {
            $target = $this->instantiateArrayAccessOrArray($metadata, $context);
        }

        // Add the target to cache

        $context(ObjectCache::class)?->saveTarget(
            source: $source,
            targetType: $metadata->getTargetType(),
            target: $target,
        );

        // determine if target allows deletion

        $allowDelete =
            $context(SourceAttributes::class)?->get(AllowTargetDelete::class) !== null
            || $context(TargetAttributes::class)?->get(AllowDelete::class) !== null;

        // Transform the source

        $transformed = $this->transformTraversableSource(
            source: $source,
            target: $target,
            metadata: $metadata,
            context: $context,
        );

        if ($allowDelete) {
            $values = [];
        } else {
            $values = null;
        }

        foreach ($transformed as $key => $value) {
            if ($key === null) {
                $target[] = $value;
            } else {
                $target[$key] = $value;
            }

            if (\is_array($values)) {
                $values[] = $value;
            }
        }

        // if target allows delete, remove values in the target that are not in
        // the values array

        if (\is_array($values) && is_iterable($target)) {
            /**
             * @psalm-suppress RedundantConditionGivenDocblockType
             */
            $isList = \is_array($target) && array_is_list($target);

            foreach ($target as $key => $value) {
                if (!\in_array($value, $values, true)) {
                    unset($target[$key]);
                }
            }

            // renumber array if it is a list

            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (\is_array($target) && $isList) {
                /** @psalm-suppress RedundantFunctionCall */
                $target = array_values($target);
            }
        }

        return $target;
    }

    /**
     * @phpstan-ignore-next-line
     */
    private function instantiateArrayAccessOrArray(
        ArrayLikeMetadata $metadata,
        Context $context,
    ): \ArrayAccess|array {
        // if it wants an array, just return it. easy.

        if ($metadata->isTargetArray()) {
            return [];
        }

        // otherwise, we try to instantiate the target class

        $class = $metadata->getTargetClass();
        $reflectionClass = new \ReflectionClass($class);

        // if instantiable, instantiate

        if ($reflectionClass->isInstantiable()) {
            try {
                $result = $reflectionClass->newInstance();
            } catch (\ReflectionException) {
                throw new ClassNotInstantiableException($class, context: $context);
            }

            if (!$result instanceof \ArrayAccess) {
                throw new InvalidArgumentException(\sprintf('Target class "%s" must implement "\ArrayAccess"', $class), context: $context);
            }

            return $result;
        }

        // at this point, $class must be an interface or an abstract class.
        // the following is a heuristic for some popular situations

        switch (true) {
            case $class === \ArrayAccess::class:
                if ($metadata->targetMemberKeyCanBeOtherThanIntOrString()) {
                    return new HashTable();
                }

                return new \ArrayObject();

            case $class === Collection::class:
            case $class === ReadableCollection::class:
                return new ArrayCollection();
        }

        throw new InvalidArgumentException(\sprintf('We do not know how to create an instance of "%s"', $class));
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        $sourceTypes = [
            TypeFactory::objectOfClass(\Traversable::class),
            TypeFactory::array(),
            TypeFactory::null(),
        ];

        $targetTypes = [
            TypeFactory::objectOfClass(Collection::class),
            TypeFactory::objectOfClass(ReadableCollection::class),
            TypeFactory::objectOfClass(ArrayCollection::class),
            TypeFactory::objectOfClass(\ArrayObject::class),
            TypeFactory::objectOfClass(\ArrayIterator::class),
            TypeFactory::objectOfClass(\ArrayAccess::class),
            TypeFactory::objectOfClass(CollectionInterface::class),
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
