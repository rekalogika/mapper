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

use Rekalogika\Mapper\Attribute\DateTimeOptions;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\Context\SourcePropertyAttributes;
use Rekalogika\Mapper\Transformer\Context\TargetPropertyAttributes;
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
        Context $context,
    ): mixed {
        // determine source time zone

        $sourceTimeZone = $context(SourcePropertyAttributes::class)
            ?->get(DateTimeOptions::class)
            ?->getTimeZone()
            ?? new \DateTimeZone(date_default_timezone_get());

        // if source is scalar, we convert it to DateTimeInterface first

        if (\is_scalar($source)) {
            $isSourceScalar = true;

            $sourceFormat = $context(SourcePropertyAttributes::class)
                ?->get(DateTimeOptions::class)
                ?->getFormat();

            // if not string, cast to string, and set source format to 'U' (unix
            // timestamp) if not set

            if (!\is_string($source)) {
                $source = (string) $source;
                $sourceFormat = $sourceFormat ?? 'U';
            }

            if ($sourceFormat !== null) {
                $source = DatePoint::createFromFormat(
                    format: $sourceFormat,
                    datetime: $source,
                    timezone: $sourceTimeZone,
                );

                // "The timezone parameter and the current timezone are ignored
                // when the datetime parameter either contains a UNIX timestamp
                // or specifies a timezone" so we do it again here

                $source = $source->setTimezone($sourceTimeZone);
            } else {
                $source = new DatePoint($source, $sourceTimeZone);
            }
        } elseif ($source instanceof \DateTimeInterface) {
            $isSourceScalar = false;

            $source = DatePoint::createFromInterface($source);
            $source = $source->setTimezone($sourceTimeZone);
        } else {
            throw new InvalidArgumentException(\sprintf('Source must be DateTimeInterface, "%s" given', get_debug_type($source)), context: $context);
        }

        // get target time zone. null means no conversion

        $targetTimeZone = $context(TargetPropertyAttributes::class)
            ?->get(DateTimeOptions::class)
            ?->getTimeZone();

        // if target is mutable, just set directly on the instance and return it

        if ($target instanceof \DateTime) {
            $target->setTimestamp($source->getTimestamp());

            if ($targetTimeZone !== null) {
                $target->setTimezone($targetTimeZone);
            } elseif ($isSourceScalar) {
                $target->setTimezone($source->getTimezone());
            }

            return $target;
        }

        // transformations to datetime objects

        if (TypeCheck::isObjectOfType($targetType, \DateTime::class)) {
            $result = \DateTime::createFromInterface($source);

            if ($targetTimeZone !== null) {
                $result = $result->setTimezone($targetTimeZone);
            } elseif ($isSourceScalar) {
                $result = $result->setTimezone($source->getTimezone());
            }

            return $result;
        }

        if (TypeCheck::isObjectOfType($targetType, DatePoint::class)) {
            $result = DatePoint::createFromInterface($source);

            if ($targetTimeZone !== null) {
                $result = $result->setTimezone($targetTimeZone);
            } elseif ($isSourceScalar) {
                $result = $result->setTimezone($source->getTimezone());
            }

            return $result;
        }

        if (TypeCheck::isObjectOfType(
            $targetType,
            \DateTimeInterface::class,
            \DateTimeImmutable::class,
        )) {
            $result = DatePoint::createFromInterface($source);

            if ($targetTimeZone !== null) {
                $result = $result->setTimezone($targetTimeZone);
            } elseif ($isSourceScalar) {
                $result = $result->setTimezone($source->getTimezone());
            }

            return $result;
        }

        // transformation to string

        if (TypeCheck::isString($targetType)) {
            $result = \DateTimeImmutable::createFromInterface($source);

            if ($targetTimeZone !== null) {
                $result = $result->setTimezone($targetTimeZone);
            }

            $targetFormat = $context(TargetPropertyAttributes::class)
                ?->get(DateTimeOptions::class)
                ?->getFormat()
                ?? \DateTimeInterface::ATOM;

            return $result->format($targetFormat);
        }

        // transformation to integer

        if (TypeCheck::isInt($targetType)) {
            $result = \DateTimeImmutable::createFromInterface($source);

            if ($targetTimeZone !== null) {
                $result = $result->setTimezone($targetTimeZone);
            }

            $targetFormat = $context(TargetPropertyAttributes::class)
                ?->get(DateTimeOptions::class)
                ?->getFormat()
                ?? 'U';

            return (int) $result->format($targetFormat);
        }

        // transformation to float

        if (TypeCheck::isFloat($targetType)) {
            $result = \DateTimeImmutable::createFromInterface($source);

            if ($targetTimeZone !== null) {
                $result = $result->setTimezone($targetTimeZone);
            }

            $targetFormat = $context(TargetPropertyAttributes::class)
                ?->get(DateTimeOptions::class)
                ?->getFormat()
                ?? 'U';

            return (float) $result->format($targetFormat);
        }

        throw new InvalidArgumentException(\sprintf('Target must be DateTime, DateTimeImmutable, or DatePoint, "%s" given', get_debug_type($targetType)), context: $context);
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        $dateTimeTypes = [
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::objectOfClass(\DateTime::class),
            TypeFactory::objectOfClass(\DateTimeImmutable::class),
            TypeFactory::objectOfClass(DatePoint::class),
        ];

        foreach ($dateTimeTypes as $dateTimeType) {
            // from scalar to datetime types
            yield new TypeMapping(
                TypeFactory::string(),
                $dateTimeType,
            );

            yield new TypeMapping(
                TypeFactory::int(),
                $dateTimeType,
            );

            yield new TypeMapping(
                TypeFactory::float(),
                $dateTimeType,
            );

            // from DateTimeInterface to datetime types
            yield new TypeMapping(
                TypeFactory::objectOfClass(\DateTimeInterface::class),
                $dateTimeType,
            );
        }

        // from datetime to string
        yield new TypeMapping(
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::string(),
        );

        // from datetime to integer
        yield new TypeMapping(
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::int(),
        );

        // from datetime to float
        yield new TypeMapping(
            TypeFactory::objectOfClass(\DateTimeInterface::class),
            TypeFactory::float(),
        );
    }
}
