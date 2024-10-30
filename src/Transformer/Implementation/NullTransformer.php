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
use Rekalogika\Mapper\Transformer\Exception\NullSourceButMandatoryTargetException;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final readonly class NullTransformer implements TransformerInterface
{
    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        // if the source is null & the target is an object, ignore it by
        // returning the existing target. if the existing target is not already
        // an object, throw an exception

        if (TypeCheck::isObject($targetType)) {
            if ($target === null) {
                throw new NullSourceButMandatoryTargetException(
                    targetType: $targetType,
                    context: $context,
                );
            }

            return $target;
        }

        if ($target !== null) {
            throw new InvalidArgumentException('Target must be null');
        }

        if (TypeCheck::isString($targetType)) {
            return '';
        }

        if (TypeCheck::isInt($targetType)) {
            return 0;
        }

        if (TypeCheck::isFloat($targetType)) {
            return 0.0;
        }

        if (TypeCheck::isBool($targetType)) {
            return false;
        }

        if (TypeCheck::isArray($targetType)) {
            return [];
        }

        if (TypeCheck::isNull($targetType)) {
            return null;
        }

        throw new InvalidArgumentException(\sprintf('Target must be scalar, "%s" given.', get_debug_type($targetType)), context: $context);
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::null(), TypeFactory::string());
        yield new TypeMapping(TypeFactory::null(), TypeFactory::int());
        yield new TypeMapping(TypeFactory::null(), TypeFactory::float());
        yield new TypeMapping(TypeFactory::null(), TypeFactory::bool());
        yield new TypeMapping(TypeFactory::null(), TypeFactory::array());
        yield new TypeMapping(TypeFactory::null(), TypeFactory::object(), true);
        yield new TypeMapping(TypeFactory::mixed(), TypeFactory::null());
    }
}
