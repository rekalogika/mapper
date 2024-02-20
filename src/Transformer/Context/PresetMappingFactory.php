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
use Rekalogika\Mapper\Transformer\Model\SplObjectStorageWrapper;
use Rekalogika\Mapper\Util\ClassUtil;

final readonly class PresetMappingFactory
{
    private function __construct()
    {
    }

    public static function fromObjectCache(ObjectCache $objectCache): PresetMapping
    {
        $objectCacheWeakMap = $objectCache->getInternalMapping();

        /** @var SplObjectStorageWrapper<object,\ArrayObject<class-string,object>> */
        $presetMapping = new SplObjectStorageWrapper(new \SplObjectStorage());

        /**
         * @var object $source
         * @var \ArrayObject<class-string,object> $classToTargetMapping
         */
        foreach ($objectCacheWeakMap as $source => $classToTargetMapping) {
            $newTargetClass = ClassUtil::determineRealClassFromPossibleProxy($source::class);
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

        return new PresetMapping($presetMapping);
    }
}
