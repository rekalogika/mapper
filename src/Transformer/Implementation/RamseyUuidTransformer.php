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

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeUtil;
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

final readonly class RamseyUuidTransformer implements TransformerInterface
{
    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        if ($targetType === null) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Target type is null when trying to transform type "%s" to "%s", using source "%s".',
                    TypeUtil::getDebugType($sourceType),
                    TypeUtil::getDebugType($targetType),
                    get_debug_type($source),
                ),
            );
        }

        // wants to convert string to uuid

        if (\is_string($source)) {
            $targetClass = $targetType instanceof ObjectType ? $targetType->getClassName() : null;

            if ($targetClass === null) {
                throw new InvalidArgumentException(
                    \sprintf(
                        'Target class is null when trying to transform type "%s" to "%s", using source "%s".',
                        TypeUtil::getDebugType($sourceType),
                        TypeUtil::getDebugType($targetType),
                        get_debug_type($source),
                    ),
                );
            }

            return Uuid::fromString($source);
        }

        // wants to convert uuid to string

        if ($source instanceof UuidInterface) {
            if (TypeCheck::isString($targetType)) {
                return $source->toString();
            } elseif ($targetType instanceof ObjectType && $targetType->getClassName() === UuidInterface::class) {
                return $source;
            }

            return $source->toString();
        }

        throw new InvalidArgumentException(
            \sprintf(
                'Trying to transform type "%s" to "%s", using source "%s".',
                TypeUtil::getDebugType($sourceType),
                TypeUtil::getDebugType($targetType),
                get_debug_type($source),
            ),
        );
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(
            TypeFactory::objectOfClass(UuidInterface::class),
            TypeFactory::string(),
        );

        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(UuidInterface::class),
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(UuidInterface::class),
            TypeFactory::objectOfClass(UuidInterface::class),
        );
    }
}
