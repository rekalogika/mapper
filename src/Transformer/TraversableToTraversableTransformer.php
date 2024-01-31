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
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Transformer\Model\TraversableCountableWrapper;
use Rekalogika\Mapper\Transformer\Trait\TraversableTransformerTrait;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class TraversableToTraversableTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;
    use TraversableTransformerTrait;

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

        // We cannot work with an existing Traversable value

        if ($target !== null) {
            throw new InvalidArgumentException(sprintf('This transformer does not support existing value, "%s" found.', get_debug_type($target)), context: $context);
        }

        // create transformation metadata

        $targetMetadata = $this->arrayLikeMetadataFactory
            ->createArrayLikeMetadata($targetType);

        // Transform source

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $transformed = $this->transformTraversableSource(
            source: $source,
            target: null,
            targetMetadata: $targetMetadata,
            context: $context,
        );

        $target = (function () use ($transformed): \Traversable {
            foreach ($transformed as $row) {
                /** @psalm-suppress MixedAssignment */
                $key = $row['key'];
                /** @psalm-suppress MixedAssignment */
                $value = $row['value'];

                yield $key => $value;
            }
        })();

        // Wrap the result if the source is countable

        if ($source instanceof \Countable) {
            $target = new TraversableCountableWrapper($target, $source);
        } elseif (is_array($source)) {
            $target = new TraversableCountableWrapper($target, count($source));
        }

        // Add to cache

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
