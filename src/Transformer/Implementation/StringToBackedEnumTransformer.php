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
use Rekalogika\Mapper\Transformer\Exception\InvalidTypeInArgumentException;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final readonly class StringToBackedEnumTransformer implements TransformerInterface
{
    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if (!is_string($source)) {
            throw new InvalidArgumentException(sprintf('Source must be string, "%s" given', get_debug_type($source)), context: $context);
        }

        $class = $targetType?->getClassName();

        if (null === $class || !\enum_exists($class)) {
            throw new InvalidTypeInArgumentException('Target must be an enum class-string, "%s" given', $targetType, context: $context);
        }

        // @todo maybe add option to handle values not in the enum
        if (is_a($class, \BackedEnum::class, true)) {
            /** @var class-string<\BackedEnum> $class */
            return $class::from($source);
        }

        throw new InvalidArgumentException(sprintf('Target must be an enum class-string, "%s" given', get_debug_type($target)), context: $context);
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(\BackedEnum::class),
            true,
        );
    }
}
