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

namespace Rekalogika\Mapper\Tests\Fixtures\InvalidTransformer;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

class InvalidTransformer implements TransformerInterface
{
    public function getSupportedTransformation(): iterable
    {
        /**
         * @psalm-suppress InvalidClass
         * @psalm-suppress UndefinedClass
         * @psalm-suppress MixedArgument
         */
        yield new TypeMapping(
            // @phpstan-ignore-next-line
            TypeFactory::objectOfClass(InvalidClass::class),
            // @phpstan-ignore-next-line
            TypeFactory::objectOfClass(AnotherInvalidClass::class)
        );
    }

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        throw new InvalidArgumentException('Should never reach here');
    }
}
