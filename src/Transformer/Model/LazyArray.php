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

namespace Rekalogika\Mapper\Transformer\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Rekalogika\Mapper\CollectionInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\BadMethodCallException;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Trait\ArrayLikeTransformerTrait;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements CollectionInterface<TKey,TValue>
 * @implements Collection<TKey,TValue>
 * @internal
 */
final class LazyArray implements CollectionInterface, Collection
{
    use MainTransformerAwareTrait;
    use ArrayLikeTransformerTrait;

    /**
     * @var array<TKey,TValue>
     */
    private array $cachedData = [];

    /**
     * @var list<TKey>
     */
    private array $cachedKeyOrder = [];

    private bool $isCacheComplete = false;

    /**
     * @param (\Traversable<TKey,mixed>&\ArrayAccess<TKey,mixed>&\Countable)|array<TKey,mixed> $source
     */
    public function __construct(
        private (\Traversable&\ArrayAccess&\Countable)|array $source,
        MainTransformerInterface $mainTransformer,
        private ArrayLikeMetadata $metadata,
        private Context $context,
    ) {
        $this->mainTransformer = $mainTransformer;
    }

    //
    // ArrayAccess methods
    //

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->cachedData[$offset]) || isset($this->source[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        if (isset($this->cachedData[$offset])) {
            return $this->cachedData[$offset];
        }

        /**
         * @var TKey $key
         * @var TValue $value
         */
        [$key, $value] = $this->transformMember(
            sourceMemberKey: $offset,
            sourceMemberValue: $this->source[$offset],
            metadata: $this->metadata,
            context: $this->context,
        );

        return $this->cachedData[$key] = $value;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('LazyArray is immutable.');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('LazyArray is immutable.');
    }

    //
    // IteratorAggregate methods
    //

    /** @psalm-suppress InvalidReturnType */
    #[\Override]
    public function getIterator(): \Traversable
    {
        if ($this->isCacheComplete) {
            foreach ($this->cachedKeyOrder as $key) {
                yield $key => $this->cachedData[$key];
            }

            return;
        }

        $this->cachedKeyOrder = [];

        /**
         * @var mixed $sourceMemberValue
         */
        foreach ($this->source as $sourceMemberKey => $sourceMemberValue) {
            if (isset($this->cachedData[$sourceMemberKey])) {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->cachedKeyOrder[] = $sourceMemberKey;
                yield $sourceMemberKey => $this->cachedData[$sourceMemberKey];

                continue;
            }

            /**
             * @var TKey|null $key
             * @var TValue $value
             */
            [$key, $value] = $this->transformMember(
                sourceMemberKey: $sourceMemberKey,
                sourceMemberValue: $sourceMemberValue,
                metadata: $this->metadata,
                context: $this->context,
            );

            if ($key === null) {
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->cachedData[] = $value;
                $lastKey = array_key_last($this->cachedData);
                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->cachedKeyOrder[] = $lastKey;

                yield $lastKey => $value;

                continue;
            } elseif ($key !== $sourceMemberKey) {
                throw new LogicException(
                    \sprintf(
                        'Transformation in keys detected. Source key: "%s", transformed key: "%s".',
                        $sourceMemberKey,
                        $key,
                    ),
                    context: $this->context,
                );
            }

            /** @psalm-suppress InvalidPropertyAssignmentValue */
            $this->cachedData[$key] = $value;
            /** @psalm-suppress InvalidPropertyAssignmentValue */
            $this->cachedKeyOrder[] = $key;

            yield $key => $value;
        }

        $this->isCacheComplete = true;
    }

    //
    // Countable methods
    //

    #[\Override]
    public function count(): int
    {
        return \count($this->source);
    }

    //
    // ReadableCollection methods
    //

    #[\Override]
    public function contains(mixed $element): bool
    {
        foreach ($this->getIterator() as $value) {
            if ($value === $element) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    #[\Override]
    public function containsKey(string|int $key): bool
    {
        return $this->offsetExists($key);
    }

    #[\Override]
    public function get(string|int $key): mixed
    {
        return $this->offsetGet($key);
    }

    #[\Override]
    public function getKeys(): array
    {
        $keys = [];

        foreach ($this->getIterator() as $key => $_) {
            $keys[] = $key;
        }

        return $keys;
    }

    #[\Override]
    public function getValues(): array
    {
        $values = [];

        foreach ($this->getIterator() as $_ => $value) {
            $values[] = $value;
        }

        return $values;
    }

    #[\Override]
    public function toArray(): array
    {
        $array = [];

        foreach ($this->getIterator() as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }

    #[\Override]
    public function first(): mixed
    {
        $first = null;

        foreach ($this->getIterator() as $value) {
            $first = $value;
            break;
        }

        return $first ?? false;
    }

    #[\Override]
    public function last(): mixed
    {
        $last = null;

        foreach ($this->getIterator() as $value) {
            $last = $value;
        }

        return $last ?? false;
    }

    #[\Override]
    public function key(): int|string|null
    {
        throw new BadMethodCallException('Unsupported method');
    }

    #[\Override]
    public function current(): mixed
    {
        throw new BadMethodCallException('Unsupported method');
    }

    #[\Override]
    public function next(): mixed
    {
        throw new BadMethodCallException('Unsupported method');
    }

    #[\Override]
    public function slice(int $offset, ?int $length = null): array
    {
        $result = [];
        $i = 0;

        foreach ($this->getIterator() as $key => $value) {
            if ($i >= $offset) {
                $result[$key] = $value;
            }

            if ($length !== null && \count($result) >= $length) {
                break;
            }

            $i++;
        }

        return $result;
    }

    #[\Override]
    public function exists(\Closure $p): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($p($key, $value)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function filter(\Closure $p): Collection
    {
        $filtered = [];

        foreach ($this->getIterator() as $key => $value) {
            if ($p($value, $key)) {
                $filtered[$key] = $value;
            }
        }

        return new ArrayCollection($filtered);
    }

    #[\Override]
    public function map(\Closure $func): Collection
    {
        $mapped = [];

        foreach ($this->getIterator() as $key => $value) {
            $mapped[$key] = $func($value);
        }

        return new ArrayCollection($mapped);
    }

    #[\Override]
    public function partition(\Closure $p): array
    {
        $matches = [];
        $nonMatches = [];

        foreach ($this->getIterator() as $key => $value) {
            if ($p($key, $value)) {
                $matches[$key] = $value;
            } else {
                $nonMatches[$key] = $value;
            }
        }

        return [new ArrayCollection($matches), new ArrayCollection($nonMatches)];
    }

    #[\Override]
    public function forAll(\Closure $p): bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if (!$p($key, $value)) {
                return false;
            }
        }

        return true;
    }

    #[\Override]
    public function indexOf(mixed $element): int|string|bool
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($value === $element) {
                return $key;
            }
        }

        return false;
    }

    #[\Override]
    public function findFirst(\Closure $p): mixed
    {
        foreach ($this->getIterator() as $key => $value) {
            if ($p($key, $value)) {
                return $value;
            }
        }

        return null;
    }

    #[\Override]
    public function reduce(\Closure $func, mixed $initial = null): mixed
    {
        $carry = $initial;

        foreach ($this->getIterator() as $key => $value) {
            $carry = $func($carry, $value);
        }

        return $carry;
    }

    //
    // Collection methods
    //

    #[\Override]
    public function add(mixed $element): void
    {
        throw new BadMethodCallException('LazyArray is immutable.');
    }

    #[\Override]
    public function clear(): void
    {
        throw new BadMethodCallException('LazyArray is immutable.');
    }

    #[\Override]
    public function remove(string|int $key): mixed
    {
        throw new BadMethodCallException('LazyArray is immutable.');
    }

    #[\Override]
    public function removeElement(mixed $element): bool
    {
        throw new BadMethodCallException('LazyArray is immutable.');
    }

    #[\Override]
    public function set(string|int $key, mixed $value): void
    {
        throw new BadMethodCallException('LazyArray is immutable.');
    }
}
