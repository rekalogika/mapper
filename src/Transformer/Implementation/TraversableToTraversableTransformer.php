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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Model\TraversableCountableWrapper;
use Rekalogika\Mapper\Transformer\Trait\ArrayLikeTransformerTrait;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\PropertyInfo\Type;

final class TraversableToTraversableTransformer implements TransformerInterface, MainTransformerAwareInterface
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
        Context $context
    ): mixed {
        if (null === $source) {
            $source = [];
        }

        if (null === $targetType) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // The source must be a Traversable or an array (a.k.a. iterable).

        if (!$source instanceof \Traversable && !is_array($source)) {
            throw new InvalidArgumentException(sprintf('Source must be instance of "\Traversable" or "array", "%s" given', get_debug_type($source)), context: $context);
        }

        // We cannot work with an existing Traversable value

        if (null !== $target) {
            throw new InvalidArgumentException(sprintf('This transformer does not support existing value, "%s" found.', get_debug_type($target)), context: $context);
        }

        // create transformation metadata

        if (null === $sourceType) {
            $sourceType = TypeGuesser::guessTypeFromVariable($source);
        }

        $metadata = $this->arrayLikeMetadataFactory
            ->createArrayLikeMetadata($sourceType, $targetType)
        ;

        // Transform source

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $target = $this->transformTraversableSource(
            source: $source,
            target: null,
            metadata: $metadata,
            context: $context,
        );

        // Wrap the result if the source is countable

        if ($source instanceof \Countable) {
            $target = new TraversableCountableWrapper($target, $source);
        } elseif (is_array($source)) {
            $target = new TraversableCountableWrapper($target, count($source));
        }

        // Add to cache

        $context(ObjectCache::class)?->saveTarget(
            source: $source,
            targetType: $targetType,
            target: $target,
        );

        return $target;
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
