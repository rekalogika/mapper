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
use Rekalogika\Mapper\Exception\UnexpectedValueException;
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
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        $presetMapping = $context(PresetMapping::class);

        if ($presetMapping === null) {
            throw new RefuseToTransformException();
        }

        if (!TypeCheck::isObject($targetType)) {
            throw new UnexpectedValueException('Target type must be an object type');
        }

        $class = $targetType?->getClassName();

        if (!is_string($class) || !class_exists($class)) {
            throw new UnexpectedValueException('Target type must be a valid class name');
        }

        if (!is_object($source)) {
            throw new UnexpectedValueException('Source must be an object');
        }

        try {
            return $presetMapping->findResult($source, $class);
        } catch (PresetMappingNotFound) {
            throw new RefuseToTransformException();
        }
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object(), true);
    }
}
