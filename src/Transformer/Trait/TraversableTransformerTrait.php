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
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\PropertyInfo\Type;

trait TraversableTransformerTrait
{
    /**
     * @param iterable<array-key,mixed> $source
     * @param \ArrayAccess<mixed,mixed>|array<array-key,mixed>|null $target
     * @return \Traversable<array{key:mixed,value:mixed}>
     */
    private function transformTraversableSource(
        iterable $source,
        \ArrayAccess|array|null $target,
        Type $targetType,
        Context $context
    ): \Traversable {
        $targetMemberKeyType = $targetType->getCollectionKeyTypes();
        $targetMemberKeyTypeIsInt = count($targetMemberKeyType) === 1
            && TypeCheck::isInt($targetMemberKeyType[0]);
        $targetMemberValueType = $targetType->getCollectionValueTypes();

        $i = 0;

        /**
         * @var array-key $sourceMemberKey
         * @var mixed $sourceMemberValue
         * */
        foreach ($source as $sourceMemberKey => $sourceMemberValue) {
            // if target has int key type but the source has string key type,
            // we discard the source key & use null (i.e. $target[] = $value)

            if ($targetMemberKeyTypeIsInt && is_string($sourceMemberKey)) {
                $targetMemberKey = null;
                $path = sprintf('[%d]', $i);
            } else {
                /** @var string|int */
                $targetMemberKey = $sourceMemberKey;
                $path = sprintf('[%s]', $sourceMemberKey);
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

            yield [
                'key' => $targetMemberKey,
                'value' => $targetMemberValue,
            ];

            $i++;
        }
    }
}
