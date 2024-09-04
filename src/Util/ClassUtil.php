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
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return class-string<T>
     */
    public static function determineRealClassFromPossibleProxy(string $class): string
    {
        $inputClass = $class;

        $pos = strrpos($class, '\\__CG__\\');

        if (false === $pos) {
            $pos = strrpos($class, '\\__PM__\\');
        }

        if (false !== $pos) {
            $class = substr($class, $pos + 8);
        }

        if (!class_exists($class)) {
            throw new UnexpectedValueException(sprintf(
                'Trying to resolve the real class from possible proxy class "%s", got "%s", but the class does not exist',
                $inputClass,
                $class
            ));
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (!is_a($inputClass, $class, true)) {
            /** @psalm-suppress NoValue */
            throw new UnexpectedValueException(sprintf(
                'Trying to resolve the real class from possible proxy class "%s", got "%s", but the proxy "%s" is not a subclass of "%s"',
                $inputClass,
                $class,
                $inputClass,
                $class,
            ));
        }

        /** @var class-string<T> $class */

        return $class;
    }

    /**
     * @param class-string|\ReflectionClass<object> $class
     */
    public static function getLastModifiedTime(
        \ReflectionClass|string $class
    ): int {
        if (is_string($class)) {
            $class = new \ReflectionClass($class);
        }

        if ($class->isInternal()) {
            return 0;
        }

        $fileName = $class->getFileName();

        if (false === $fileName) {
            throw new \UnexpectedValueException(sprintf(
                'Failed to get file name for class "%s"',
                $class->getName()
            ));
        }

        $mtime = filemtime($fileName);

        if (false === $mtime) {
            throw new \UnexpectedValueException(sprintf(
                'Failed to get last modified time for class "%s"',
                $class->getName()
            ));
        }

        if ($parent = $class->getParentClass()) {
            return max(
                $mtime,
                self::getLastModifiedTime($parent)
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
         *
         * @psalm-suppress InternalClass
         */
        return Hydrator::getPropertyScopes($class);
    }

    /**
     * @param array<int,string> $eagerProperties
     *
     * @return array<string,true>
     */
    public static function getSkippedProperties(
        string $class,
        array $eagerProperties
    ): array {
        $propertyScopes = self::getPropertyScopes($class);

        $skippedProperties = [];

        foreach ($propertyScopes as $scope => $data) {
            $name = $data[1];
            if (in_array($name, $eagerProperties, true)) {
                $skippedProperties[$scope] = true;
            }
        }

        return $skippedProperties;
    }

    /**
     * @param class-string|object $objectOrClass
     *
     * @return array<int,class-string>
     */
    public static function getAllClassesFromObject(
        object|string $objectOrClass
    ): array {
        $class = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        $parents = class_parents($class, true);
        if (false === $parents) {
            $parents = [];
        }

        $interfaces = class_implements($class, true);
        if (false === $interfaces) {
            $interfaces = [];
        }

        return [
            $class,
            ...array_values($parents),
            ...array_values($interfaces),
        ];
    }
}
