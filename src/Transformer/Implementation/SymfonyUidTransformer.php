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
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeUtil;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final readonly class SymfonyUidTransformer implements TransformerInterface
{
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if ($targetType === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Target type is null when trying to transform type "%s" to "%s", using source "%s".',
                    TypeUtil::getDebugType($sourceType),
                    TypeUtil::getDebugType($targetType),
                    \get_debug_type($source)
                )
            );
        }

        // wants to convert string to uuid or ulid

        if (is_string($source)) {
            $targetClass = $targetType->getClassName();

            if ($targetClass === null) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Target class is null when trying to transform type "%s" to "%s", using source "%s".',
                        TypeUtil::getDebugType($sourceType),
                        TypeUtil::getDebugType($targetType),
                        \get_debug_type($source)
                    )
                );
            }

            if ($targetClass === Uuid::class) {
                return Uuid::fromString($source);
            } elseif ($targetClass === Ulid::class) {
                return Ulid::fromString($source);
            }

            return Uuid::fromString($source);
        }

        // wants to convert uuid to string

        if ($source instanceof Uuid) {
            if ($targetType->getBuiltinType() === Type::BUILTIN_TYPE_STRING) {
                return $source->toRfc4122();
            } elseif ($targetType->getClassName() === Uuid::class) {
                return $source;
            }

            return $source->toRfc4122();
        }

        // wants to convert ulid to string

        if ($source instanceof Ulid) {
            if ($targetType->getBuiltinType() === Type::BUILTIN_TYPE_STRING) {
                return $source->toBase32();
            } elseif ($targetType->getClassName() === Ulid::class) {
                return $source;
            }

            return $source->toBase32();
        }

        throw new InvalidArgumentException(
            sprintf(
                'Trying to transform type "%s" to "%s", using source "%s".',
                TypeUtil::getDebugType($sourceType),
                TypeUtil::getDebugType($targetType),
                \get_debug_type($source)
            )
        );
    }

    public function getSupportedTransformation(): iterable
    {
        // uuid

        yield new TypeMapping(
            TypeFactory::objectOfClass(Uuid::class),
            TypeFactory::string(),
        );

        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(Uuid::class),
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(Uuid::class),
            TypeFactory::objectOfClass(Uuid::class),
        );

        // ulid

        yield new TypeMapping(
            TypeFactory::objectOfClass(Ulid::class),
            TypeFactory::string(),
        );

        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(Ulid::class),
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(Ulid::class),
            TypeFactory::objectOfClass(Ulid::class),
        );
    }
}
