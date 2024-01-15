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

namespace Rekalogika\Mapper\Util;

use Rekalogika\Mapper\Attribute\MapperAttributeInterface;

class AttributeUtil
{
    /**
     * @var array<class-string,array<int,\ReflectionAttribute<MapperAttributeInterface>>>
     */
    private static array $attributesIncludingParentsCache = [];

    /**
     * @param \ReflectionClass<object> $class
     * @return array<int,\ReflectionAttribute<MapperAttributeInterface>>
     */
    public static function getAttributesIncludingParents(\ReflectionClass $class): array
    {
        $className = $class->getName();

        if (isset(self::$attributesIncludingParentsCache[$className])) {
            return self::$attributesIncludingParentsCache[$className];
        }

        $attributes = [];

        while ($class !== false) {
            $attributes = array_merge(
                $attributes,
                self::getAttributes($class)
            );

            $class = $class->getParentClass();
        }

        foreach (class_implements($className) as $interface) {
            $interface = new \ReflectionClass($interface);

            $attributes = array_merge(
                $attributes,
                self::getAttributes($interface)
            );
        }

        return self::$attributesIncludingParentsCache[$className] = $attributes;
    }

    /**
     * @var array<class-string,array<int,\ReflectionAttribute<MapperAttributeInterface>>>
     */
    private static array $attributes = [];

    /**
     * @param \ReflectionClass<object> $class
     * @return array<int,\ReflectionAttribute<MapperAttributeInterface>>
     */
    public static function getAttributes(\ReflectionClass $class): array
    {
        $className = $class->getName();

        if (isset(self::$attributes[$className])) {
            return self::$attributes[$className];
        }

        $attributes = [];

        $attributes = $class->getAttributes(
            MapperAttributeInterface::class,
            \ReflectionAttribute::IS_INSTANCEOF
        );

        return self::$attributes[$className] = $attributes;
    }
}
