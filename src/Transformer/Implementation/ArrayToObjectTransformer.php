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
use Rekalogika\Mapper\Serializer\DenormalizerContext;
use Rekalogika\Mapper\Transformer\Exception\InvalidTypeInArgumentException;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Map an array to an object. Uses the Symfony Serializer component as the
 * backend.
 *
 * @deprecated Use ArrayObjectTransformer instead
 */
final readonly class ArrayToObjectTransformer implements TransformerInterface
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private ?string $denormalizerFormat = null,
    ) {}

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        if (!\is_array($source)) {
            throw new InvalidArgumentException(\sprintf('Source must be array, "%s" given', get_debug_type($source)), context: $context);
        }

        if (!TypeCheck::isObject($targetType)) {
            throw new InvalidTypeInArgumentException('Target type must be an object, "%s" given', $targetType, context: $context);
        }

        $denormalizerContext = $context(DenormalizerContext::class)?->toArray() ?? [];

        if ($target !== null) {
            if (!\is_object($target)) {
                throw new InvalidArgumentException(\sprintf('Target must be an object, "%s" given', get_debug_type($target)), context: $context);
            }

            $targetClass = $target::class;
            $denormalizerContext[AbstractNormalizer::OBJECT_TO_POPULATE] = $target;
        } else {
            $targetClass = $targetType?->getClassName() ?? \stdClass::class;
        }

        return $this->denormalizer->denormalize(
            $source,
            $targetClass,
            $this->denormalizerFormat,
            $denormalizerContext,
        );
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::array(), TypeFactory::object(), true);
    }
}
