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

namespace Rekalogika\Mapper\Tests\Fixtures\Transformer;

use Brick\Money\Money;
use Rekalogika\Mapper\Contracts\TransformerInterface;
use Rekalogika\Mapper\Contracts\TypeMapping;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Tests\Fixtures\Money\MoneyDto;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

class MoneyToMoneyDtoTransformer implements TransformerInterface
{
    // This tells the library that this transformer supports the transformation
    // from the Money object to the MoneyDto object, and vice versa.
    //
    // The TypeFactory methods are convenience methods for creating the
    // PropertyInfo Type objects.

    public function getSupportedTransformation(): iterable
    {

        yield new TypeMapping(
            TypeFactory::objectOfClass(Money::class),
            TypeFactory::objectOfClass(MoneyDto::class)
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(MoneyDto::class),
            TypeFactory::objectOfClass(Money::class)
        );
    }

    // This method is called when the mapper is trying to transform Money to
    // MoneyDto, and vice versa.
    //
    // The $source and $target parameters are the source and target objects,
    // respectively. $target is usually null, unless there is already an
    // existing value in the target object.
    //
    // $sourceType and $targetType are the types of the source and target, in
    // the form of PropertyInfo Type object.
    //
    // The TypeCheck class is a convenience class for verifying the type
    // specified by a Type object.

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        array $context
    ): mixed {
        if (
            $source instanceof Money
            && TypeCheck::isObjectOfType($targetType, MoneyDto::class)
        ) {
            return new MoneyDto(
                amount: $source->getAmount()->__toString(),
                currency: $source->getCurrency()->getCurrencyCode(),
            );
        }

        if (
            $source instanceof MoneyDto
            && TypeCheck::isObjectOfType($targetType, Money::class)
        ) {
            return Money::of(
                $source->getAmount(),
                $source->getCurrency()
            );
        }

        throw new InvalidArgumentException('Unsupported transformation');
    }
}
