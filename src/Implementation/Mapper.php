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

namespace Rekalogika\Mapper\Implementation;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Util\TypeFactory;

/**
 * @internal
 */
final readonly class Mapper implements MapperInterface
{
    public function __construct(
        private MainTransformerInterface $transformer,
    ) {
    }

    /**
     * @template T of object
     * @param class-string<T>|T $target
     * @return T
     */
    public function map(mixed $source, object|string $target, ?Context $context = null): object
    {
        if (is_string($target)) {
            $targetClass = $target;
            if (
                !class_exists($targetClass)
                && !\interface_exists($targetClass)
            ) {
                throw new UnexpectedValueException(sprintf('The target class "%s" does not exist.', $targetClass));
            }
            $targetType = TypeFactory::objectOfClass($targetClass);
            $target = null;
        } else {
            /** @var T $target */
            $targetClass = get_class($target);
            $targetType = TypeFactory::objectOfClass($targetClass);
        }

        /** @var mixed */
        $target = $this->transformer->transform(
            source: $source,
            target: $target,
            sourceType: null,
            targetTypes: [$targetType],
            context: $context ?? Context::create(),
        );

        if ($target === null) {
            throw new UnexpectedValueException(sprintf('The mapper returned null, expecting "%s".', $targetClass));
        }

        if (!is_object($target) || !is_a($target, $targetClass)) {
            throw new UnexpectedValueException(sprintf('The mapper did not return the variable of expected class, expecting "%s", returned "%s".', $targetClass, get_debug_type($target)));
        }

        return $target;
    }
}
