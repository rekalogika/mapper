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
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\PropertyInfo\Type;

/**
 * Map between DateTime and string. If a string is involved
 */
final readonly class DateTimeTransformer implements TransformerInterface
{
    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if (\is_string($source)) {
            $source = new DatePoint($source);
        }

        if (!$source instanceof \DateTimeInterface) {
            throw new InvalidArgumentException(sprintf('Source must be DateTimeInterface, "%s" given', get_debug_type($source)), context: $context);
        }

        // if target is mutable, just set directly on the instance and return it
        if ($target instanceof \DateTime) {
            $target->setTimestamp($source->getTimestamp());

            return $target;
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

        throw new InvalidArgumentException(sprintf('Target must be DateTime, DateTimeImmutable, or DatePoint, "%s" given', get_debug_type($targetType)), context: $context);
    }

    #[\Override]
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
