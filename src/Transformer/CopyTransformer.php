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

use Rekalogika\Mapper\MainTransformer\Context;
use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Symfony\Component\PropertyInfo\Type;

final class CopyTransformer implements TransformerInterface
{
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if (!is_object($source)) {
            return $source;
        }

        $clonable = (new \ReflectionClass($source))->isCloneable();

        if (!$clonable) {
            return $source;
        }
        return clone $source;
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(MixedType::instance(), MixedType::instance());
    }
}
