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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\ListInterface;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadata;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Trait\ArrayLikeTransformerTrait;

/**
 * Discards source key, and use incremental integer key in the target.
 *
 * @template TValue
 * @implements ListInterface<int,TValue>
 * @internal
 */
final class LazyList implements ListInterface
{
    use MainTransformerAwareTrait;
    use ArrayLikeTransformerTrait;

    /**
     * @var array<int,TValue>
     */
    private array $cachedData = [];

    private bool $isCached = false;

    /**
     * @param (\Traversable<mixed,mixed>&\ArrayAccess<mixed,mixed>&\Countable)|array<int|string,mixed> $source
     */
    public function __construct(
        private (\Traversable&\ArrayAccess&\Countable)|array $source,
        MainTransformerInterface $mainTransformer,
        private ArrayLikeMetadata $metadata,
        private Context $context,
    ) {
        $this->mainTransformer = $mainTransformer;
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->cachedData[$offset]) || isset($this->source[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->isCached) {
            foreach ($this->getIterator() as $i) {
                // do nothing
            }
        }

        return $this->cachedData[$offset];
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('LazyArray is immutable.');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('LazyArray is immutable.');
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        if ($this->isCached) {
            yield from $this->cachedData;

            return;
        }

        $i = 0;

        /**
         * @var mixed $sourceMemberValue
         */
        foreach ($this->source as $sourceMemberKey => $sourceMemberValue) {
            /**
             * @var TValue $value
             */
            [, $value] = $this->transformMember(
                sourceMemberKey: $sourceMemberKey,
                sourceMemberValue: $sourceMemberValue,
                metadata: $this->metadata,
                context: $this->context,
            );

            $this->cachedData[$i] = $value;

            yield $value;

            $i++;
        }

        $this->isCached = true;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->source);
    }
}
