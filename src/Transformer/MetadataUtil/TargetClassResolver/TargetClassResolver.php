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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\TargetClassResolver;

use Rekalogika\Mapper\Attribute\InheritanceMap;
use Rekalogika\Mapper\Transformer\Exception\SourceClassNotInInheritanceMapException;
use Rekalogika\Mapper\Transformer\MetadataUtil\TargetClassResolverInterface;
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final readonly class TargetClassResolver implements TargetClassResolverInterface
{
    public function resolveTargetClass(
        string $sourceClass,
        string $targetClass,
    ): string {
        $sourceReflection = new \ReflectionClass($sourceClass);
        $targetReflection = new \ReflectionClass($targetClass);

        $targetAttributes = $targetReflection->getAttributes(InheritanceMap::class);

        if ($targetAttributes !== []) {
            // if the target has an InheritanceMap, we try to resolve the target
            // class using the InheritanceMap

            $inheritanceMap = $targetAttributes[0]->newInstance();

            $resolvedTargetClass = $inheritanceMap->getTargetClassFromSourceClass($sourceClass);

            if ($resolvedTargetClass === null) {
                throw new SourceClassNotInInheritanceMapException($sourceClass, $targetClass);
            }

            return $resolvedTargetClass;
        } elseif ($targetReflection->isAbstract() || $targetReflection->isInterface()) {
            // if target doesn't have an inheritance map, but is also abstract
            // or an interface, we try to find the InheritanceMap from the
            // source

            $sourceClasses = ClassUtil::getAllClassesFromObject($sourceClass);

            foreach ($sourceClasses as $currentSourceClass) {
                $sourceReflection = new \ReflectionClass($currentSourceClass);
                $sourceAttributes = $sourceReflection->getAttributes(InheritanceMap::class);

                if ($sourceAttributes !== []) {
                    $inheritanceMap = $sourceAttributes[0]->newInstance();

                    $resolvedTargetClass = $inheritanceMap->getSourceClassFromTargetClass($sourceClass);

                    if ($resolvedTargetClass === null) {
                        throw new SourceClassNotInInheritanceMapException($currentSourceClass, $targetClass);
                    }

                    return $resolvedTargetClass;
                }
            }
        }

        return $targetClass;
    }
}
