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

use Rekalogika\Mapper\Transformer\Exception\PresetMappingNotFound;

/**
 * Contains preset object to object mapping, used by `PresetTransformer`.
 */
final readonly class PresetMapping
{
    /**
     * @var \WeakMap<object,\ArrayObject<class-string,object>>
     */
    private \WeakMap $mappings;

    /**
     * @param iterable<object,iterable<class-string,object>> $mappings
     */
    public function __construct(iterable $mappings = [])
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

    public function mergeFrom(self $presetMapping): void
    {
        /**
         * @var object                            $source
         * @var \ArrayObject<class-string,object> $classToTargetMapping
         */
        foreach ($presetMapping->mappings as $source => $classToTargetMapping) {
            if (!$this->mappings->offsetExists($source)) {
                /** @var \ArrayObject<class-string,object> */
                $arrayObject = new \ArrayObject();
                $this->mappings->offsetSet($source, $arrayObject);
            }

            foreach ($classToTargetMapping as $class => $target) {
                $this->mappings->offsetGet($source)->offsetSet($class, $target);
            }
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $targetClass
     *
     * @return T
     *
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

        if (!$result instanceof $targetClass) {
            throw new PresetMappingNotFound();
        }

        return $result;
    }
}
