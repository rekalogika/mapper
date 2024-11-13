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

namespace Rekalogika\Mapper\Tests\Services\Issue232;

use Brick\Math\BigDecimal;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final readonly class BigDecimalTransformer implements TransformerInterface
{
    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): BigDecimal|string {
        if ($source instanceof BigDecimal) {
            return (string) $source;
        }

        /**
         * @psalm-suppress MixedArgument
         * @phpstan-ignore argument.type
         */
        return BigDecimal::of($source)->toBigDecimal();
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(BigDecimal::class),
        );

        yield new TypeMapping(
            TypeFactory::int(),
            TypeFactory::objectOfClass(BigDecimal::class),
        );

        yield new TypeMapping(
            TypeFactory::float(),
            TypeFactory::objectOfClass(BigDecimal::class),
        );

        yield new TypeMapping(
            TypeFactory::objectOfClass(BigDecimal::class),
            TypeFactory::string(),
        );
    }
}
