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
use Rekalogika\Mapper\Serializer\NormalizerContext;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Map an object to an array. Uses the Symfony Serializer component as the
 * backend.
 */
final readonly class ObjectToArrayTransformer implements TransformerInterface
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private ?string $normalizerFormat = null
    ) {
    }

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if (!is_object($source)) {
            throw new InvalidArgumentException(sprintf('Source must be object, "%s" given', get_debug_type($source)), context: $context);
        }

        $normalizerContext = $context(NormalizerContext::class)?->toArray() ?? [];

        return $this->normalizer->normalize(
            $source,
            $this->normalizerFormat,
            $normalizerContext
        );
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::array());
    }
}
