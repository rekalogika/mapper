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

use Rekalogika\Mapper\ArrayInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Contracts\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Trait\ArrayLikeTransformerTrait;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements ArrayInterface<TKey,TValue>
 */
class LazyArray implements ArrayInterface
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

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->cachedData[$offset]) || isset($this->source[$offset]);
    }

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

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('LazyArray is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('LazyArray is immutable.');
    }

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
                $this->cachedKeyOrder[] = $sourceMemberKey;
                yield $sourceMemberKey => $this->cachedData[$sourceMemberKey];

                continue;
            }

            /**
             * @var TKey $key
             * @var TValue $value
             */
            [$key, $value] = $this->transformMember(
                sourceMemberKey: $sourceMemberKey,
                sourceMemberValue: $sourceMemberValue,
                metadata: $this->metadata,
                context: $this->context,
            );

            if ($key !== $sourceMemberKey) {
                throw new LogicException(
                    'Transformation in keys detected.',
                    context: $this->context
                );
            }

            $this->cachedData[$key] = $value;
            $this->cachedKeyOrder[] = $key;

            yield $key => $value;
        }

        $this->isCacheComplete = true;
    }

    public function count(): int
    {
        return count($this->source);
    }
}