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

namespace Rekalogika\Mapper\Transformer\Trait;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\Model\SplObjectStorageWrapper;
use Rekalogika\Mapper\Util\TypeCheck;

trait ArrayLikeTransformerTrait
{
    /**
     * @param iterable<mixed,mixed> $source
     * @param \ArrayAccess<mixed,mixed>|array<array-key,mixed>|null $target
     * @return \Traversable<mixed,mixed>
     */
    private function transformTraversableSource(
        iterable $source,
        \ArrayAccess|array|null $target,
        ArrayLikeMetadata $metadata,
        Context $context
    ): \Traversable {
        // if the source is SplObjectStorage, we wrap it to fix the iterator

        if ($source instanceof \SplObjectStorage) {
            $source = new SplObjectStorageWrapper($source);
        }

        $i = 0;

        /**
         * @var mixed $sourceMemberKey
         * @var mixed $sourceMemberValue
         */
        foreach ($source as $sourceMemberKey => $sourceMemberValue) {
            /**
             * @var mixed $key
             * @var mixed $value
             */
            [$key, $value] = $this->transformMember(
                counter: $i,
                sourceMemberKey: $sourceMemberKey,
                sourceMemberValue: $sourceMemberValue,
                target: $target,
                metadata: $metadata,
                context: $context,
            );

            yield $key => $value;

            $i++;
        }
    }

    /**
     * @param null|\ArrayAccess<mixed,mixed>|array<mixed,mixed> $target
     * @return array{0:mixed,1:mixed}
     */
    private function transformMember(
        mixed $sourceMemberKey,
        mixed $sourceMemberValue,
        ArrayLikeMetadata $metadata,
        Context $context,
        null|\ArrayAccess|array $target = null,
        ?int $counter = null,
    ): array {
        // optimization: we try not to use the main tranformer to transform
        // the source member key to the target member key type

        if (\is_string($sourceMemberKey)) {
            // if the key is a string

            if ($metadata->targetMemberKeyCanBeIntOnly()) {
                // if target has int key type but the source has string key
                // type, we discard the source key & use null key (i.e.
                // $target[] = $value)

                $targetMemberKey = null;
                $path = sprintf('[%d]', $counter ?? -1);
            } elseif ($metadata->targetMemberKeyCanBeString()) {
                // if target has string key type, we use the source key as
                // the target key and let PHP cast it to string

                $targetMemberKey = $sourceMemberKey;
                $path = sprintf('[%s]', $sourceMemberKey);
            } else {
                // otherwise, the target must be non-int & non-string, so we
                // delegate the transformation to the main transformer

                /** @var mixed */
                $targetMemberKey = $this->getMainTransformer()->transform(
                    source: $sourceMemberKey,
                    target: null,
                    sourceType: null,
                    targetTypes: $metadata->getTargetMemberKeyTypes(),
                    context: $context,
                    path: '(key)',
                );

                if ($targetMemberKey instanceof \Stringable) {
                    $path = sprintf('[%s]', (string) $targetMemberKey);
                } else {
                    $path = sprintf('[%s]', get_debug_type($targetMemberKey));
                }
            }
        } elseif (\is_int($sourceMemberKey)) {
            // if the key is an integer

            if (
                $metadata->targetMemberKeyCanBeInt()
                || $metadata->targetMemberKeyCanBeString()
            ) {
                // if the target has int or string key type, we use the
                // source key as the target key, and let PHP cast it if
                // needed

                $targetMemberKey = $sourceMemberKey;
                $path = sprintf('[%s]', $sourceMemberKey);
            } else {
                // otherwise, the target must be non-int & non-string, so we
                // delegate the transformation to the main transformer

                /** @var mixed */
                $targetMemberKey = $this->getMainTransformer()->transform(
                    source: $sourceMemberKey,
                    target: null,
                    sourceType: null,
                    targetTypes: $metadata->getTargetMemberKeyTypes(),
                    context: $context,
                    path: '(key)',
                );

                if ($targetMemberKey instanceof \Stringable) {
                    $path = sprintf('[%s]', (string) $targetMemberKey);
                } else {
                    $path = sprintf('[%s]', get_debug_type($targetMemberKey));
                }
            }
        } else {
            // If the type of the key is not an string or int, we delegate
            // the transformation to the main transformer

            /** @var mixed */
            $targetMemberKey = $this->getMainTransformer()->transform(
                source: $sourceMemberKey,
                target: null,
                sourceType: null,
                targetTypes: $metadata->getTargetMemberKeyTypes(),
                context: $context,
                path: '(key)',
            );

            if ($targetMemberKey instanceof \Stringable) {
                $path = sprintf('[%s]', (string) $targetMemberKey);
            } else {
                $path = sprintf('[%s]', get_debug_type($targetMemberKey));
            }
        }

        // if the target value type is untyped, we use the source value as
        // the target value

        if ($metadata->targetMemberValueIsUntyped()) {
            return [$targetMemberKey, $sourceMemberValue];
        }

        // if the target value types has a type compatible with the source
        // value, then we use the source value as the target value

        foreach ($metadata->getTargetMemberValueTypes() as $targetMemberValueType) {
            if (TypeCheck::isVariableInstanceOf($sourceMemberValue, $targetMemberValueType)) {
                return [$targetMemberKey, $sourceMemberValue];
            }
        }

        // Get the existing member value from the target

        try {
            if ($target !== null && $targetMemberKey !== null) {
                /**
                 * @var mixed
                 * @psalm-suppress MixedArrayOffset
                 * @psalm-suppress MixedArrayTypeCoercion
                 */
                $targetMemberValue = $target[$targetMemberKey] ?? null;
            } else {
                $targetMemberValue = null;
            }
        } catch (\Throwable) { // @phpstan-ignore-line
            $targetMemberValue = null;
        }

        // if target member value is not an object we delete it because it
        // will not be used anyway

        if (!\is_object($targetMemberValue)) {
            $targetMemberValue = null;
        }

        // now transform the source member value to the type of the target
        // member value

        /** @var mixed */
        $targetMemberValue = $this->getMainTransformer()->transform(
            source: $sourceMemberValue,
            target: $targetMemberValue,
            sourceType: null,
            targetTypes: $metadata->getTargetMemberValueTypes(),
            context: $context,
            path: $path,
        );

        return [$targetMemberKey, $targetMemberValue];
    }
}
