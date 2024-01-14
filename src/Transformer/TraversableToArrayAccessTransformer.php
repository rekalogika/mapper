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
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\InvalidTypeInArgumentException;
use Rekalogika\Mapper\Transformer\Exception\MissingMemberKeyTypeException;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class TraversableToArrayAccessTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

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

        // If the target is not provided, instantiate it, and add to cache.

        if ($target === null) {
            $target = $this->instantiateArrayAccessOrArray($targetType, $context);
        }

        $context(ObjectCache::class)
            ->saveTarget($source, $targetType, $target, $context);

        // Prepare variables for the output loop

        $targetMemberKeyType = $targetType->getCollectionKeyTypes();
        $targetMemberKeyTypeIsMissing = count($targetMemberKeyType) === 0;
        $targetMemberKeyTypeIsInt = count($targetMemberKeyType) === 1
            && TypeCheck::isInt($targetMemberKeyType[0]);
        $targetMemberValueType = $targetType->getCollectionValueTypes();

        $i = 0;

        /** @var mixed $sourceMemberValue */
        foreach ($source as $sourceMemberKey => $sourceMemberValue) {
            /** @var mixed $sourceMemberKey */

            if (is_string($sourceMemberKey) || is_int($sourceMemberKey)) {
                // if the key is a simple type: int|string

                if ($targetMemberKeyTypeIsInt && is_string($sourceMemberKey)) {
                    // if target has int key type but the source has string key type,
                    // we discard the source key & use null (i.e. $target[] = $value)

                    $targetMemberKey = null;
                    $path = sprintf('[%d]', $i);
                } else {
                    $targetMemberKey = $sourceMemberKey;
                    $path = sprintf('[%s]', $sourceMemberKey);
                }
            } else {
                // If the type of the key is a complex type (not int or string).
                // i.e. an ArrayObject can have an object as its key.

                // Refuse to continue if the target key type is not provided

                if ($targetMemberKeyTypeIsMissing) {
                    throw new MissingMemberKeyTypeException($sourceType, $targetType, context: $context);
                }

                // If provided, we transform the source key to the key type of
                // the target

                /** @var mixed */
                $targetMemberKey = $this->getMainTransformer()->transform(
                    source: $sourceMemberKey,
                    target: null,
                    targetTypes: $targetMemberKeyType,
                    context: $context,
                );

                if ($targetMemberKey instanceof \Stringable) {
                    $path = sprintf('[%s]', $targetMemberKey);
                } else {
                    $path = sprintf('[%s]', get_debug_type($targetMemberKey));
                }
            }

            // Get the existing member value from the target

            /** @var mixed $targetMemberValue */
            $targetMemberValue = $target[$sourceMemberKey] ?? null;

            // if target member value is not an object we delete it because it
            // will be removed anyway

            if (!is_object($targetMemberValue)) {
                $targetMemberValue = null;
            }

            // now transform the source member value to the type of the target
            // member value

            /** @var mixed */
            $targetMemberValue = $this->getMainTransformer()->transform(
                source: $sourceMemberValue,
                target: $targetMemberValue,
                targetTypes: $targetMemberValueType,
                context: $context,
                path: $path,
            );

            if ($targetMemberKey === null) {
                $target[] = $targetMemberValue;
            } else {
                $target[$targetMemberKey] = $targetMemberValue;
            }

            $i++;
        }

        return $target;
    }

    /**
     * @return \ArrayAccess<mixed,mixed>|array<array-key,mixed>
     */
    private function instantiateArrayAccessOrArray(
        Type $targetType,
        Context $context,
    ): \ArrayAccess|array {
        // if it wants an array, just return it. easy.

        if (TypeCheck::isArray($targetType)) {
            return [];
        }

        $class = $targetType->getClassName();

        if ($class === null) {
            throw new InvalidTypeInArgumentException('Target must be an instance of "\ArrayAccess" or "array, "%s" given', $targetType, context: $context);
        }

        if (!class_exists($class) && !\interface_exists($class)) {
            throw new InvalidArgumentException(sprintf('Target class "%s" does not exist', $class), context: $context);
        }

        $reflectionClass = new \ReflectionClass($class);

        if (!$reflectionClass->implementsInterface(\ArrayAccess::class)) {
            throw new InvalidArgumentException(sprintf('Target class "%s" must implement "\ArrayAccess"', $class), context: $context);
        }

        // if instantiable, instantiate

        if ($reflectionClass->isInstantiable()) {
            try {
                $result = $reflectionClass->newInstance();
            } catch (\ReflectionException) {
                throw new ClassNotInstantiableException($class, context: $context);
            }

            if (!$result instanceof \ArrayAccess) {
                throw new InvalidArgumentException(sprintf('Instantiated class "%s" does not implement "\ArrayAccess"', $class), context: $context);
            }

            return $result;
        }

        // at this point, $class must be an interface or an abstract class.
        // the following is a heuristic for some popular situations

        $concreteClass = match ($class) {
            \ArrayAccess::class => \ArrayObject::class,
            Collection::class => ArrayCollection::class,
            default => throw new InvalidArgumentException(sprintf('We do not know how to create an instance of "%s"', $class)),
        };

        if (!class_exists($concreteClass)) {
            throw new InvalidArgumentException(sprintf('Concrete class "%s" does not exist', $concreteClass));
        }

        return new $concreteClass();
    }

    public function getSupportedTransformation(): iterable
    {
        $sourceTypes = [
            TypeFactory::objectOfClass(\Traversable::class),
            TypeFactory::array(),
        ];

        $targetTypes = [
            TypeFactory::objectOfClass(Collection::class),
            TypeFactory::objectOfClass(\ArrayObject::class),
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
