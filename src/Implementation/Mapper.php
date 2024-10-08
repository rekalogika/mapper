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

use Rekalogika\Mapper\CacheWarmer\WarmableMainTransformerInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableMapperInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\IterableMapperInterface;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\Util\TypeFactory;

/**
 * @internal
 */
final readonly class Mapper implements MapperInterface, IterableMapperInterface, WarmableMapperInterface
{
    public function __construct(
        private MainTransformerInterface $transformer,
    ) {}

    /**
     * @template T of object
     * @param class-string<T>|T $target
     * @return T
     */
    #[\Override]
    public function map(object $source, object|string $target, ?Context $context = null): object
    {
        if (\is_string($target)) {
            $targetClass = $target;

            if (
                !class_exists($targetClass)
                && !interface_exists($targetClass)
            ) {
                throw new UnexpectedValueException(\sprintf('The target class "%s" does not exist.', $targetClass));
            }

            $targetType = TypeFactory::objectOfClass($targetClass);
            $target = null;
        } else {
            /** @var T $target */
            $targetClass = $target::class;
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
            throw new UnexpectedValueException(\sprintf('The mapper returned null, expecting "%s".', $targetClass));
        }

        if (!\is_object($target) || !$target instanceof $targetClass) {
            throw new UnexpectedValueException(\sprintf('The mapper did not return the variable of expected class, expecting "%s", returned "%s".', $targetClass, get_debug_type($target)));
        }

        return $target;
    }

    /**
     * @template T of object
     * @param iterable<mixed> $source
     * @param class-string<T> $target
     * @return iterable<T>
     */
    #[\Override]
    public function mapIterable(
        iterable $source,
        string $target,
        ?Context $context = null,
    ): iterable {
        $targetClass = $target;

        if (
            !class_exists($targetClass)
            && !interface_exists($targetClass)
        ) {
            throw new UnexpectedValueException(\sprintf('The target class "%s" does not exist.', $targetClass));
        }

        $targetType = TypeFactory::objectOfClass($targetClass);

        /** @var mixed $item */
        foreach ($source as $item) {
            $result = $this->transformer->transform(
                source: $item,
                target: null,
                sourceType: null,
                targetTypes: [$targetType],
                context: $context ?? Context::create(),
            );

            if (!\is_object($result) || !$result instanceof $target) {
                throw new UnexpectedValueException(\sprintf('The mapper did not return the variable of expected class, expecting "%s", returned "%s".', $targetClass, get_debug_type($target)));
            }

            yield $result;
        }
    }

    #[\Override]
    public function warmingMap(string $sourceClass, string $targetClass): void
    {
        $transformer = $this->transformer;

        if (!$transformer instanceof WarmableMainTransformerInterface) {
            return;
        }

        $sourceType = TypeFactory::objectOfClass($sourceClass);
        $targetType = TypeFactory::objectOfClass($targetClass);
        $context = Context::create();

        $transformer->warmingTransform(
            sourceTypes: [$sourceType],
            targetTypes: [$targetType],
            context: $context,
        );
    }
}
