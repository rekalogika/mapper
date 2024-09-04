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
use Rekalogika\Mapper\Transformer\Context\PresetMapping;
use Rekalogika\Mapper\Transformer\Exception\PresetMappingNotFound;
use Rekalogika\Mapper\Transformer\Exception\RefuseToTransformException;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final readonly class PresetTransformer implements TransformerInterface
{
    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        $presetMapping = $context(PresetMapping::class);

        if (null === $presetMapping) {
            throw new RefuseToTransformException();
        }

        if (!TypeCheck::isObject($targetType)) {
            throw new RefuseToTransformException();
        }

        $class = $targetType?->getClassName();

        if (!is_string($class) || !class_exists($class)) {
            throw new RefuseToTransformException();
        }

        if (!is_object($source)) {
            throw new RefuseToTransformException();
        }

        try {
            return $presetMapping->findResult($source, $class);
        } catch (PresetMappingNotFound) {
            throw new RefuseToTransformException();
        }
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object(), true);
    }
}
