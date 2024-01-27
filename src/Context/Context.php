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

use Rekalogika\Mapper\Exception\LogicException;

/**
 * @immutable
 */
final readonly class Context
{
    /**
     * @param array<class-string,object> $context
     */
    private function __construct(
        readonly private array $context = []
    ) {
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

    public function with(object $value): self
    {
        $class = get_class($value);
        $context = $this->context;
        $context[$class] = $value;

        return self::createFrom($context);
    }

    public function without(object|string $value): self
    {
        $class = is_string($value) ? $value : get_class($value);

        if (!isset($this->context[$class])) {
            throw new LogicException(sprintf('Object "%s" not in context.', $class));
        }

        $context = $this->context;
        unset($context[$class]);

        return self::createFrom($context);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     * @throws ContextMemberNotFoundException
     */
    public function get(string $class): object
    {
        // @phpstan-ignore-next-line
        return $this->context[$class] ?? throw new ContextMemberNotFoundException();
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function __invoke(string $class): object
    {
        // @phpstan-ignore-next-line
        return $this->context[$class] ?? throw new ContextMemberNotFoundException();
    }
}
