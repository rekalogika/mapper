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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\ObjectCache\Exception\CachedTargetObjectNotFoundException;
use Rekalogika\Mapper\ObjectCache\Exception\CircularReferenceException;
use Rekalogika\Mapper\ObjectCache\Exception\NonSimpleTypeException;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Symfony\Component\PropertyInfo\Type;

final class ObjectCache
{
    /**
     * @var \SplObjectStorage<object,\ArrayObject<string,object>>
     */
    private \SplObjectStorage $cache;

    /**
     * @var \SplObjectStorage<object,\ArrayObject<string,true>>
     */
    private \SplObjectStorage $preCache;

    public function __construct(
        private TypeResolverInterface $typeResolver
    ) {
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->cache = new \SplObjectStorage();
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->preCache = new \SplObjectStorage();
    }

    private function isBlacklisted(mixed $source): bool
    {
        return $source instanceof \DateTimeInterface;
    }

    private function assertSimpleType(Type $type, Context $context): void
    {
        if (!$this->typeResolver->isSimpleType($type)) {
            throw new NonSimpleTypeException($type, context: $context);
        }
    }

    /**
     * Precaching indicates we want to cache the target, but haven't done so
     * yet. If the object is still in precached status, obtaining it from the
     * cache will yield an exception. If the target is finally cached, it is
     * no longer in precached status.
     *
     * @param mixed $source
     * @param Type $targetType
     * @return void
     */
    public function preCache(mixed $source, Type $targetType, Context $context): void
    {
        if (!is_object($source)) {
            return;
        }

        if ($this->isBlacklisted($source)) {
            return;
        }

        $this->assertSimpleType($targetType, $context);

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        if (!isset($this->preCache[$source])) {
            /** @var \ArrayObject<string,true> */
            $arrayObject = new \ArrayObject();
            $this->preCache[$source] = $arrayObject;
        }

        $this->preCache->offsetGet($source)->offsetSet($targetTypeString, true);
    }

    private function isPreCached(mixed $source, Type $targetType, Context $context): bool
    {
        if (!is_object($source)) {
            return false;
        }

        $this->assertSimpleType($targetType, $context);

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        return isset($this->preCache[$source][$targetTypeString]);
    }

    private function removePrecache(mixed $source, Type $targetType, Context $context): void
    {
        if (!is_object($source)) {
            return;
        }

        $this->assertSimpleType($targetType, $context);

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        if (isset($this->preCache[$source][$targetTypeString])) {
            unset($this->preCache[$source][$targetTypeString]);
        }
    }

    public function containsTarget(mixed $source, Type $targetType, Context $context): bool
    {
        if (!is_object($source)) {
            return false;
        }

        if ($this->isBlacklisted($source)) {
            return false;
        }

        $this->assertSimpleType($targetType, $context);

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        return isset($this->cache[$source][$targetTypeString]);
    }

    public function getTarget(mixed $source, Type $targetType, Context $context): mixed
    {
        if ($this->isPreCached($source, $targetType, $context)) {
            throw new CircularReferenceException($source, $targetType, context: $context);
        }

        if ($this->isBlacklisted($source)) {
            throw new CachedTargetObjectNotFoundException(context: $context);
        }

        if (!is_object($source)) {
            throw new CachedTargetObjectNotFoundException(context: $context);
        }

        $this->assertSimpleType($targetType, $context);

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        /** @var object */
        return $this->cache[$source][$targetTypeString]
            ?? throw new CachedTargetObjectNotFoundException();
    }

    public function saveTarget(
        mixed $source,
        Type $targetType,
        mixed $target,
        Context $context,
        bool $addIfAlreadyExists = false,
    ): void {
        if (!is_object($source) || !is_object($target)) {
            return;
        }

        if ($this->isBlacklisted($source)) {
            return;
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);

        if (
            $addIfAlreadyExists === false
            && $this->containsTarget($source, $targetType, $context)
        ) {
            throw new LogicException(sprintf(
                'Target object for source object "%s" and target type "%s" already exists',
                get_class($source),
                $targetTypeString
            ));
        }

        if (!isset($this->cache[$source])) {
            /** @var \ArrayObject<string,object> */
            $arrayObject = new \ArrayObject();
            $this->cache[$source] = $arrayObject;
        }

        $this->cache->offsetGet($source)->offsetSet($targetTypeString, $target);
        $this->removePrecache($source, $targetType, $context);
    }
}
