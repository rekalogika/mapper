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
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Symfony\Component\PropertyInfo\Type;

final class ObjectCache
{
    /**
     * @var array<int,array<string,object>>
     */
    private array $cache = [];

    /**
     * @var array<int,array<string,true>>
     */
    private array $preCache = [];

    public function __construct(
        private TypeResolverInterface $typeResolver
    ) {
    }

    private function isBlacklisted(mixed $source): bool
    {
        return $source instanceof \DateTimeInterface;
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

        $targetTypeString = $this->typeResolver->getTypeString($targetType);
        $key = spl_object_id($source);

        if (!isset($this->preCache[$key])) {
            $this->preCache[$key] = [];
        }

        $this->preCache[$key][$targetTypeString] = true;
    }

    private function isPreCached(mixed $source, Type $targetType, Context $context): bool
    {
        if (!is_object($source)) {
            return false;
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);
        $key = spl_object_id($source);

        return isset($this->preCache[$key][$targetTypeString]);
    }

    public function containsTarget(mixed $source, Type $targetType, Context $context): bool
    {
        if (!is_object($source)) {
            return false;
        }

        if ($this->isBlacklisted($source)) {
            return false;
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);
        $key = spl_object_id($source);

        return isset($this->cache[$key][$targetTypeString]);
    }

    public function getTarget(mixed $source, Type $targetType, Context $context): mixed
    {
        if ($this->isPreCached($source, $targetType, $context)) {
            throw new CircularReferenceException($source, $targetType, context: $context);
        }

        if ($this->isBlacklisted($source)) {
            throw new CachedTargetObjectNotFoundException();
        }

        if (!is_object($source)) {
            throw new CachedTargetObjectNotFoundException();
        }

        $targetTypeString = $this->typeResolver->getTypeString($targetType);
        $key = spl_object_id($source);

        /** @var object */
        return $this->cache[$key][$targetTypeString]
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
        $key = spl_object_id($source);

        if (
            $addIfAlreadyExists === false
            && isset($this->cache[$key][$targetTypeString])
        ) {
            throw new LogicException(sprintf(
                'Target object for source object "%s" and target type "%s" already exists',
                get_class($source),
                $targetTypeString
            ));
        }

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = [];
        }

        $this->cache[$key][$targetTypeString] = $target;

        // remove precache

        if (isset($this->preCache[$key][$targetTypeString])) {
            unset($this->preCache[$key][$targetTypeString]);
        }
    }
}
