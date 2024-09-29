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

use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Symfony\Component\VarExporter\Internal\Hydrator;

/**
 * @internal
 */
final readonly class ClassUtil
{
    private function __construct() {}

    /**
     * @copyright Kévin Dunglas
     * @see https://github.com/api-platform/core/blob/main/src/Metadata/Util/ClassInfoTrait.php
     */
    public static function getRealClassName(string $className): string
    {
        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        $positionCg = strrpos($className, '\\__CG__\\');
        $positionPm = strrpos($className, '\\__PM__\\');

        if (false === $positionCg && false === $positionPm) {
            return $className;
        }

        if (false !== $positionCg) {
            return substr($className, $positionCg + 8);
        }

        $className = ltrim($className, '\\');
        $pos = strrpos($className, '\\');

        if ($pos === false) {
            throw new UnexpectedValueException(\sprintf(
                'Unable to determine the real class name from the proxy class "%s"',
                $className,
            ));
        }

        return substr(
            $className,
            8 + $positionPm,
            $pos - ($positionPm + 8),
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return class-string<T>
     */
    public static function determineRealClassFromPossibleProxy(string $class): string
    {
        $realClass = self::getRealClassName($class);

        if (!class_exists($realClass)) {
            throw new UnexpectedValueException(\sprintf(
                'Trying to resolve the real class from possible proxy class "%s", got "%s", but the class does not exist',
                $class,
                $realClass,
            ));
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (!is_a($class, $realClass, true)) {
            /** @psalm-suppress NoValue */
            throw new UnexpectedValueException(\sprintf(
                'Trying to resolve the real class from possible proxy class "%s", got "%s", but the proxy "%s" is not a subclass of "%s"',
                $class,
                $realClass,
                $class,
                $realClass,
            ));
        }

        /** @var class-string<T> $realClass */

        return $realClass;
    }

    /**
     * @param class-string|\ReflectionClass<object> $class
     */
    public static function getLastModifiedTime(
        string|\ReflectionClass $class,
    ): int {
        if (\is_string($class)) {
            $class = new \ReflectionClass($class);
        }

        if ($class->isInternal()) {
            return 0;
        }

        $fileName = $class->getFileName();

        if ($fileName === false) {
            throw new \UnexpectedValueException(\sprintf(
                'Failed to get file name for class "%s"',
                $class->getName(),
            ));
        }

        $mtime = filemtime($fileName);

        if ($mtime === false) {
            throw new \UnexpectedValueException(\sprintf(
                'Failed to get last modified time for class "%s"',
                $class->getName(),
            ));
        }

        if ($parent = $class->getParentClass()) {
            return max(
                $mtime,
                self::getLastModifiedTime($parent),
            );
        }

        return $mtime;
    }

    /**
     * @return array<string,array{string,string,?string,\ReflectionProperty}>
     */
    private static function getPropertyScopes(string $class): array
    {
        /**
         * @var array<string,array{string,string,?string,\ReflectionProperty}>
         * @psalm-suppress InternalClass
         */
        return Hydrator::getPropertyScopes($class);
    }

    /**
     * @param array<int,string> $eagerProperties
     * @return array<string,true>
     */
    public static function getSkippedProperties(
        string $class,
        array $eagerProperties,
    ): array {
        $propertyScopes = self::getPropertyScopes($class);

        $skippedProperties = [];

        foreach ($propertyScopes as $scope => $data) {
            $name = $data[1];
            if (\in_array($name, $eagerProperties, true)) {
                $skippedProperties[$scope] = true;
            }
        }

        return $skippedProperties;
    }


    /**
     * @param object|class-string $objectOrClass
     * @return array<int,class-string>
     */
    public static function getAllClassesFromObject(
        object|string $objectOrClass,
    ): array {
        $class = \is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        $parents = class_parents($class, true);
        if ($parents === false) {
            $parents = [];
        }

        $interfaces = class_implements($class, true);
        if ($interfaces === false) {
            $interfaces = [];
        }

        return [
            $class,
            ...array_values($parents),
            ...array_values($interfaces),
        ];
    }

    /**
     * @template T of object
     * @param class-string $class
     * @param null|class-string<T> $attributeClass
     * @param list<string>|null $methodPrefixes
     * @param list<string>|null $methods
     * @return ($attributeClass is null ? list<object> : list<T>)
     */
    public static function getPropertyAttributes(
        string $class,
        string $property,
        ?string $attributeClass,
        ?array $methodPrefixes = null,
        ?array $methods = null,
    ): array {
        $attributes = self::getAttributesFromProperty($class, $property, $attributeClass);

        foreach ($methodPrefixes ?? [] as $prefix) {
            $attributes = [
                ...$attributes,
                ...self::getAttributesFromMethod($class, $prefix . ucfirst($property), $attributeClass),
            ];
        }

        foreach ($methods ?? [] as $method) {
            $attributes = [
                ...$attributes,
                ...self::getAttributesFromMethod($class, $method, $attributeClass),
            ];
        }

        return $attributes;
    }

    /**
     * @template T of object
     * @param class-string $class
     * @param null|class-string<T> $attributeClass
     * @return ($attributeClass is null ? list<object> : list<T>)
     */
    public static function getClassAttributes(
        string $class,
        ?string $attributeClass,
    ): array {
        $classes = self::getAllClassesFromObject($class);

        $attributes = [];

        foreach ($classes as $class) {
            $reflectionClass = new \ReflectionClass($class);

            if ($attributeClass === null) {
                $reflectionAttributes = $reflectionClass
                    ->getAttributes();
            } else {
                $reflectionAttributes = $reflectionClass
                    ->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF);
            }

            foreach ($reflectionAttributes as $reflectionAttribute) {
                try {
                    $attributes[] = $reflectionAttribute->newInstance();
                } catch (\Error) {
                    // Ignore errors
                }
            }
        }

        return $attributes;
    }

    /**
     * @template T of object
     * @param class-string $class
     * @param null|class-string<T> $attributeClass
     * @return ($attributeClass is null ? list<object> : list<T>)
     */
    private static function getAttributesFromProperty(
        string $class,
        string $property,
        ?string $attributeClass,
    ): array {
        $classes = self::getAllClassesFromObject($class);

        $attributes = [];

        foreach ($classes as $class) {
            $reflectionClass = new \ReflectionClass($class);

            try {
                $reflectionProperty = $reflectionClass->getProperty($property);
            } catch (\ReflectionException) {
                continue;
            }

            if ($attributeClass === null) {
                $reflectionAttributes = $reflectionProperty
                    ->getAttributes();
            } else {
                $reflectionAttributes = $reflectionProperty
                    ->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF);
            }

            foreach ($reflectionAttributes as $reflectionAttribute) {
                try {
                    $attributes[] = $reflectionAttribute->newInstance();
                } catch (\Error) {
                    // Ignore errors
                }
            }
        }

        return $attributes;
    }

    /**
     * @template T of object
     * @param class-string $class
     * @param null|class-string<T> $attributeClass
     * @return ($attributeClass is null ? list<object> : list<T>)
     */
    private static function getAttributesFromMethod(
        string $class,
        string $method,
        ?string $attributeClass,
    ): array {
        $classes = self::getAllClassesFromObject($class);

        $attributes = [];

        foreach ($classes as $class) {
            $reflectionClass = new \ReflectionClass($class);

            try {
                $reflectionMethod = $reflectionClass->getMethod($method);
            } catch (\ReflectionException) {
                continue;
            }

            if ($attributeClass === null) {
                $reflectionAttributes = $reflectionMethod
                    ->getAttributes();
            } else {
                $reflectionAttributes = $reflectionMethod
                    ->getAttributes($attributeClass, \ReflectionAttribute::IS_INSTANCEOF);
            }

            foreach ($reflectionAttributes as $reflectionAttribute) {
                try {
                    $attributes[] = $reflectionAttribute->newInstance();
                } catch (\Error) {
                    // Ignore errors
                }
            }
        }

        return $attributes;
    }

    /**
     * @param class-string $class
     */
    public static function allowsDynamicProperties(string $class): bool
    {
        if (is_a($class, \stdClass::class, true)) {
            return true;
        }

        $class = new \ReflectionClass($class);

        do {
            if ($class->getAttributes(\AllowDynamicProperties::class) !== []) {
                return true;
            }
        } while ($class = $class->getParentClass());

        return false;
    }
}
