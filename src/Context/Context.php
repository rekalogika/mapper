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

namespace Rekalogika\Mapper\Context;

/**
 * @implements \IteratorAggregate<int,object>
 */
final class Context implements \IteratorAggregate
{
    /**
     * @param array<class-string,object> $context
     */
    private function __construct(
        private array $context = [],
    ) {}

    #[\Override]
    public function getIterator(): \Traversable
    {
        foreach ($this->context as $object) {
            yield $object;
        }
    }

    public static function create(object ...$objects): self
    {
        $context = [];

        foreach ($objects as $object) {
            $class = $object::class;
            $context[$class] = $object;
        }

        return self::createFrom($context);
    }

    /**
     * @param array<class-string,object> $context
     */
    private static function createFrom(array $context): self
    {
        return new self($context);
    }

    public function with(object ...$value): self
    {
        $clone = clone $this;

        foreach ($value as $object) {
            $class = $object::class;
            $clone->context[$class] = $object;
        }

        return $clone;
    }

    public function without(object|string $value): self
    {
        $class = \is_string($value) ? $value : $value::class;

        $clone = clone $this;

        if (isset($clone->context[$class])) {
            unset($clone->context[$class]);
        }

        return $clone;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    public function get(string $class): ?object
    {
        // @phpstan-ignore-next-line
        return $this->context[$class] ?? null;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    public function __invoke(string $class): ?object
    {
        // @phpstan-ignore-next-line
        return $this->context[$class] ?? null;
    }
}
