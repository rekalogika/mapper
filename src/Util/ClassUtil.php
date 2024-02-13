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

/**
 * @internal
 */
final readonly class ClassUtil
{
    private function __construct()
    {
    }

    /**
     * @param class-string|\ReflectionClass<object> $class
     * @return int
     */
    public static function getLastModifiedTime(
        string|\ReflectionClass $class
    ): int {
        if (is_string($class)) {
            $class = new \ReflectionClass($class);
        }

        $fileName = $class->getFileName();

        if ($fileName === false) {
            throw new \UnexpectedValueException(sprintf(
                'Failed to get file name for class "%s"',
                $class->getName()
            ));
        }

        $mtime = filemtime($fileName);

        if ($mtime === false) {
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
}
