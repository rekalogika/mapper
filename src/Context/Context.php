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

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function __invoke(string $class): object
    {
        return $this->get($class);
    }

    /**
     * @param array<int,object> $context
     * @return self
     */
    public static function create(array $context = []): self
    {
        $self = new self();

        foreach($context as $value) {
            $self = $self->with($value);
        }

        return $self;
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
}
