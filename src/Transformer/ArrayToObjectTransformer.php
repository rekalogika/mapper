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
use Rekalogika\Mapper\Exception\InvalidTypeInArgumentException;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Map an array to an object. Uses the Symfony Serializer component as the
 * backend.
 */
final class ArrayToObjectTransformer implements TransformerInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private ?string $denormalizerFormat = null
    ) {
    }

    public function transform(
        mixed $source,
        mixed $target,
        Type $sourceType,
        Type $targetType,
        array $context
    ): mixed {
        if (!is_array($source)) {
            throw new InvalidArgumentException(sprintf('Source must be array, "%s" given', get_debug_type($source)));
        }

        if (!TypeCheck::isObject($targetType)) {
            throw new InvalidTypeInArgumentException('Target type must be an object, "%s" given', $targetType);
        }

        if ($target !== null) {
            if (!is_object($target)) {
                throw new InvalidArgumentException(sprintf('Target must be an object, "%s" given', get_debug_type($target)));
            }

            $targetClass = $target::class;
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $target;
        } else {
            $targetClass = $targetType->getClassName() ?? \stdClass::class;
        }

        return $this->denormalizer->denormalize(
            $source,
            $targetClass,
            $this->denormalizerFormat,
            $context
        );
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::array(), TypeFactory::object());
    }
}
