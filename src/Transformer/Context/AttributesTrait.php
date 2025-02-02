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

namespace Rekalogika\Mapper\Transformer\Context;

use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Util\ClassUtil;

trait AttributesTrait
{
    /**
     * @var list<object>
     */
    private readonly array $objects;

    /**
     * @var array<class-string,list<object>>
     */
    private readonly array $classToObjects;

    /**
     * @param iterable<object> $attributes
     */
    public function __construct(
        iterable $attributes,
    ) {
        $objects = [];
        $classToObjects = [];

        foreach ($attributes as $attribute) {
            $objects[] = $attribute;
            $classes = ClassUtil::getAllClassesFromObject($attribute);

            foreach ($classes as $class) {
                $classToObjects[$class][] = $attribute;
            }
        }

        $this->objects = $objects;
        $this->classToObjects = $classToObjects;
    }

    /**
     * @return \Traversable<object>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->objects;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    public function get(string $class): ?object
    {
        $objects = $this->classToObjects[$class] ?? null;

        $result = $objects[0] ?? null;

        if ($result === null) {
            return null;
        }

        if (!$result instanceof $class) {
            throw new UnexpectedValueException(\sprintf('Expected an instance of %s, but got %s.', $class, get_debug_type($result)));
        }

        return $result;
    }

    public function has(string $class): bool
    {
        return isset($this->classToObjects[$class]);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return iterable<T>
     */
    public function getMultiple(string $class): iterable
    {
        /** @var list<T> */
        return $this->classToObjects[$class] ?? [];
    }

    /**
     * @return list<object>
     */
    public function toArray(): array
    {
        return $this->objects;
    }
}
