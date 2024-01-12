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
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\PropertyInfo\Type;

/**
 * Map between DateTime and string. If a string is involved
 */
final class DateTimeTransformer implements TransformerInterface
{
    public function transform(
        mixed $source,
        mixed $target,
        Type $sourceType,
        ?Type $targetType,
        array $context
    ): mixed {
        if (is_string($source)) {
            $source = new DatePoint($source);
        }

        if (!$source instanceof \DateTimeInterface) {
            throw new InvalidArgumentException(sprintf('Source must be DateTimeInterface, "%s" given', get_debug_type($source)));
        }

        // if target is mutable, just set directly on the instance and return it
        if ($target instanceof \DateTime) {
            $target->setTimestamp($source->getTimestamp());

            return $target;
        }

        if ($target !== null) {
            throw new InvalidArgumentException(sprintf('Target must be null unless it is a DateTime, "%s" given', get_debug_type($target)));
        }

        if (TypeCheck::isObjectOfType($targetType, \DateTime::class)) {
            return \DateTime::createFromInterface($source);
        }

        if (TypeCheck::isObjectOfType($targetType, DatePoint::class)) {
            return DatePoint::createFromInterface($source);
        }

        if (TypeCheck::isObjectOfType(
            $targetType,
            \DateTimeInterface::class,
            \DateTimeImmutable::class
        )) {
            return \DateTimeImmutable::createFromInterface($source);
        }

        // @todo: maybe make format configurable. reuse serializer metadata?
        if (TypeCheck::isString($targetType)) {
            return $source->format(\DateTimeInterface::ATOM);
        }

        throw new InvalidArgumentException(sprintf('Target must be DateTime, DateTimeImmutable, or DatePoint, "%s" given', get_debug_type($targetType)));
    }

    public function getSupportedTransformation(): iterable
    {
        // from string

        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(\DateTimeInterface::class)
        );

        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(\DateTime::class)
        );

        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(\DateTimeImmutable::class)
        );

        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(DatePoint::class)
        );

        // from DateTimeInterface

        yield new TypeMapping(
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::objectOfClass(\DateTimeInterface::class)
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::objectOfClass(\DateTime::class)
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::objectOfClass(\DateTimeImmutable::class)
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::objectOfClass(DatePoint::class)
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::string()
        );
    }
}
