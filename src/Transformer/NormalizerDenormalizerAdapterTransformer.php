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

use Rekalogika\Mapper\Contracts\TransformerInterface;
use Rekalogika\Mapper\Contracts\TypeMapping;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Leverages existing normalizer & denormalizer to perform mapping. It does
 * the mapping by normalizing the source object to array, then denormalizing
 * the array to target object.
 */
final class NormalizerDenormalizerAdapterTransformer implements TransformerInterface
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private string $normalizerFormat = null,
        private string $denormalizerFormat = null
    ) {
    }

    public function transform(
        mixed $source,
        mixed $target,
        Type $sourceType,
        Type $targetType,
        array $context
    ): mixed {
        $arrayForm = $this->normalizer->normalize(
            $source,
            $this->normalizerFormat,
            $context
        );

        $targetClass = $targetType->getClassName();
        if ($targetClass === null || !class_exists($targetClass)) {
            throw new InvalidArgumentException(sprintf('Target type must be an object, "%s" given', get_debug_type($targetType)));
        }

        return $this->denormalizer->denormalize(
            $arrayForm,
            $targetClass,
            $this->denormalizerFormat,
            $context
        );
    }

    public function getSupportedTransformation(): iterable
    {
        $normalizerSupports = $this->normalizer
            ->getSupportedTypes($this->normalizerFormat);

        $sourceTypes = [];
        /** @var bool|null $value */
        foreach ($normalizerSupports as $normalizerSupport => $value) {
            if (is_string($normalizerSupport) && class_exists($normalizerSupport)) {
                $sourceTypes[] = TypeFactory::objectOfClass($normalizerSupport);
            }
        }

        $denormalizerSupports = $this->denormalizer
            ->getSupportedTypes($this->denormalizerFormat);

        $targetTypes = [];
        /** @var bool|null $value */
        foreach ($denormalizerSupports as $denormalizerSupport => $value) {
            if (is_string($denormalizerSupport) && class_exists($denormalizerSupport)) {
                $targetTypes[] = TypeFactory::objectOfClass($denormalizerSupport);
            }
        }

        foreach ($sourceTypes as $sourceType) {
            foreach ($targetTypes as $targetType) {
                yield new TypeMapping($sourceType, $targetType);
            }
        }
    }
}
