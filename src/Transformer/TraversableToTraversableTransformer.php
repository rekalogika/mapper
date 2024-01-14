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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Transformer\Exception\MissingMemberKeyTypeException;
use Rekalogika\Mapper\Transformer\Exception\MissingMemberValueTypeException;
use Rekalogika\Mapper\Transformer\Model\TraversableCountableWrapper;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class TraversableToTraversableTransformer implements TransformerInterface, MainTransformerAwareInterface
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

        // We cannot work with an existing Traversable value

        if ($target !== null) {
            throw new InvalidArgumentException(sprintf('This transformer does not support existing value, "%s" found.', get_debug_type($target)), context: $context);
        }

        // We can't work if the target type doesn't contain the information
        // about the type of its member objects

        $targetMemberValueType = $targetType->getCollectionValueTypes();

        if (count($targetMemberValueType) === 0) {
            throw new MissingMemberValueTypeException($sourceType, $targetType, context: $context);
        }

        // Prepare variables for the output loop

        $targetMemberKeyType = $targetType->getCollectionKeyTypes();
        $targetMemberKeyTypeIsMissing = count($targetMemberKeyType) === 0;
        $targetMemberKeyTypeIsInt = count($targetMemberKeyType) === 1
            && TypeCheck::isInt($targetMemberKeyType[0]);

        // create generator

        $target = (function () use (
            $source,
            $targetMemberKeyTypeIsInt,
            $targetMemberKeyTypeIsMissing,
            $sourceType,
            $targetType,
            $targetMemberKeyType,
            $targetMemberValueType,
            $context
        ): \Traversable {
            $i = 0;

            /** @var mixed $sourcePropertyValue */
            foreach ($source as $sourcePropertyKey => $sourcePropertyValue) {
                /** @var mixed $sourcePropertyKey */

                if (is_string($sourcePropertyKey) || is_int($sourcePropertyKey)) {
                    // if the key is a simple type: int|string

                    if ($targetMemberKeyTypeIsInt && is_string($sourcePropertyKey)) {
                        // if target has int key type but the source has string key type,
                        // we discard the source key & use null (i.e. $target[] = $value)

                        $targetPropertyKey = null;
                        $path = sprintf('[%d]', $i);
                    } else {
                        $targetPropertyKey = $sourcePropertyKey;
                        $path = sprintf('[%s]', $sourcePropertyKey);
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
                    $targetPropertyKey = $this->getMainTransformer()->transform(
                        source: $sourcePropertyKey,
                        target: null,
                        targetTypes: $targetMemberKeyType,
                        context: $context,
                    );

                    if ($targetPropertyKey instanceof \Stringable) {
                        $path = sprintf('[%s]', $targetPropertyKey);
                    } else {
                        $path = sprintf('[%s]', get_debug_type($targetPropertyKey));
                    }
                }

                // now transform the source member value to the type of the target
                // member value

                /** @var mixed */
                $targetPropertyValue = $this->getMainTransformer()->transform(
                    source: $sourcePropertyValue,
                    target: null,
                    targetTypes: $targetMemberValueType,
                    context: $context,
                    path: $path,
                );

                yield $targetPropertyKey => $targetPropertyValue;

                $i++;
            }
        })();

        if ($source instanceof \Countable) {
            $target = new TraversableCountableWrapper($target, $source);
        } elseif (is_array($source)) {
            $target = new TraversableCountableWrapper($target, count($source));
        }

        $context(ObjectCache::class)
            ->saveTarget($source, $targetType, $target, $context);

        return $target;
    }

    public function getSupportedTransformation(): iterable
    {
        $sourceTypes = [
            TypeFactory::objectOfClass(\Traversable::class),
            TypeFactory::array(),
        ];

        $targetTypes = [
            TypeFactory::objectOfClass(\Traversable::class),
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
