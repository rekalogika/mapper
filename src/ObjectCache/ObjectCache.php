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

namespace Rekalogika\Mapper\ObjectCache;

use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\ObjectCache\Exception\CachedTargetObjectNotFoundException;
use Rekalogika\Mapper\ObjectCache\Exception\CircularReferenceException;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Symfony\Component\PropertyInfo\Type;

final class ObjectCache
{
    /**
     * @var \WeakMap<object,\ArrayObject<string,object>>
     */
    private \WeakMap $cache;

    /**
     * @var \WeakMap<object,\ArrayObject<string,true>>
     */
    private \WeakMap $preCache;

    public function __construct(
        private readonly TypeResolverInterface $typeResolver,
    ) {
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->cache = new \WeakMap();
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->preCache = new \WeakMap();
    }

    private function isExcluded(mixed $source): bool
    {
        return $source instanceof \DateTimeInterface
            || $source instanceof \UnitEnum;
    }

    /**
     * Precaching indicates we want to cache the target, but haven't done so
     * yet. If the object is still in precached status, obtaining it from the
     * cache will yield an exception. If the target is finally cached, it is
     * no longer in precached status.
     */
    public function preCache(mixed $source, Type $targetType): void
    {
        if (!\is_object($source)) {
            return;
        }

        if ($this->isExcluded($source)) {
            return;
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        if (!isset($this->preCache[$source])) {
            /** @var \ArrayObject<string,true> */
            $arrayObject = new \ArrayObject();
            $this->preCache[$source] = $arrayObject;
        }

        $this->preCache->offsetGet($source)->offsetSet($targetTypeString, true);
    }

    public function undoPreCache(mixed $source, Type $targetType): void
    {
        if (!\is_object($source)) {
            return;
        }

        if ($this->isExcluded($source)) {
            return;
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        if (isset($this->preCache[$source][$targetTypeString])) {
            unset($this->preCache[$source][$targetTypeString]);
        }
    }

    public function containsTarget(mixed $source, Type $targetType): bool
    {
        if (!\is_object($source)) {
            return false;
        }

        if ($this->isExcluded($source)) {
            return false;
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        return isset($this->cache[$source][$targetTypeString]);
    }

    public function getTarget(mixed $source, Type $targetType): mixed
    {
        if (!\is_object($source)) {
            throw new CachedTargetObjectNotFoundException();
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        // check if precached

        if (isset($this->preCache[$source][$targetTypeString])) {
            throw new CircularReferenceException();
        }

        if ($this->isExcluded($source)) {
            throw new CachedTargetObjectNotFoundException();
        }

        /** @var object */
        return $this->cache[$source][$targetTypeString]
            ?? throw new CachedTargetObjectNotFoundException();
    }

    public function saveTarget(
        mixed $source,
        Type $targetType,
        mixed $target,
        bool $addIfAlreadyExists = false,
    ): void {
        if (!\is_object($source) || !\is_object($target)) {
            return;
        }

        if ($this->isExcluded($source)) {
            return;
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        if (
            $addIfAlreadyExists === false
            && isset($this->cache[$source][$targetTypeString])
        ) {
            throw new LogicException(\sprintf(
                'Target object for source object "%s" and target type "%s" already exists',
                $source::class,
                $targetTypeString,
            ));
        }

        if (!isset($this->cache[$source])) {
            /** @var \ArrayObject<string,object> */
            $arrayObject = new \ArrayObject();
            $this->cache[$source] = $arrayObject;
        }

        $this->cache->offsetGet($source)->offsetSet($targetTypeString, $target);

        // remove precache

        if (isset($this->preCache[$source][$targetTypeString])) {
            unset($this->preCache[$source][$targetTypeString]);
        }
    }

    /**
     * @return \WeakMap<object,\ArrayObject<string,object>>
     */
    public function getInternalMapping(): \WeakMap
    {
        return $this->cache;
    }
}
