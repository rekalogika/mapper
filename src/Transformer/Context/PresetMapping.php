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

use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Transformer\Exception\PresetMappingNotFound;
use Rekalogika\Mapper\Transformer\Model\SplObjectStorageWrapper;

final readonly class PresetMapping
{
    /**
     * @var \WeakMap<object,\ArrayObject<class-string,object>>
     */
    private \WeakMap $mappings;

    /**
     * @param iterable<object,iterable<class-string,object>> $mappings
     */
    public function __construct(iterable $mappings)
    {
        /**
         * @var \WeakMap<object,\ArrayObject<class-string,object>>
         */
        $weakMap = new \WeakMap();

        foreach ($mappings as $source => $classToTargetMapping) {
            $classToTargetMappingArray = [];

            foreach ($classToTargetMapping as $class => $target) {
                $classToTargetMappingArray[$class] = $target;
            }

            $weakMap[$source] = new \ArrayObject($classToTargetMappingArray);
        }

        $this->mappings = $weakMap;
    }

    public static function fromObjectCache(ObjectCache $objectCache): self
    {
        $objectCacheWeakMap = $objectCache->getInternalMapping();

        /** @var SplObjectStorageWrapper<object,\ArrayObject<class-string,object>> */
        $presetMapping = new SplObjectStorageWrapper(new \SplObjectStorage());

        /**
         * @var object $source
         * @var \ArrayObject<class-string,object> $classToTargetMapping
         */
        foreach ($objectCacheWeakMap as $source => $classToTargetMapping) {
            $newTargetClass = $source::class;
            /** @var object */
            $newTarget = $source;

            /**
             * @var string $targetClass
             * @var object $target
             */
            foreach ($classToTargetMapping as $targetClass => $target) {
                if (!class_exists($targetClass)) {
                    continue;
                }

                $newSource = $target;

                if (!$presetMapping->offsetExists($newSource)) {
                    /** @var \ArrayObject<class-string,object> */
                    $arrayObject = new \ArrayObject();
                    $presetMapping->offsetSet($newSource, $arrayObject);
                }

                $presetMapping->offsetGet($newSource)?->offsetSet($newTargetClass, $newTarget);
            }
        }

        return new self($presetMapping);
    }

    /**
     * @template T of object
     * @param object $source
     * @param class-string<T> $targetClass
     * @return T
     * @throws PresetMappingNotFound
     */
    public function findResult(object $source, string $targetClass): object
    {
        $mappings = $this->mappings[$source] ?? null;

        if (null === $mappings) {
            throw new PresetMappingNotFound();
        }

        $result = $mappings[$targetClass] ?? null;

        if (null === $result) {
            throw new PresetMappingNotFound();
        }

        if (!($result instanceof $targetClass)) {
            throw new PresetMappingNotFound();
        }

        return $result;
    }
}
