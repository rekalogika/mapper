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

use Brick\Money\Currency;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final readonly class CurrencyTransformer implements TransformerInterface
{
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        /**
         * @psalm-suppress MixedArgument
         * @phpstan-ignore argument.type
         */
        return Currency::of($source);
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(
            TypeFactory::string(),
            TypeFactory::objectOfClass(Currency::class),
        );
    }
}
