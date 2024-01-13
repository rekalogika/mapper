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

namespace Rekalogika\Mapper\Transformer\Exception;

use Rekalogika\Mapper\Attribute\InheritanceMap;

class NotAClassException extends NotMappableValueException
{
    public function __construct(string $class)
    {
        /** @var class-string $class */

        try {
            $reflectionClass = new \ReflectionClass($class);

            if ($reflectionClass->isInterface()) {
                parent::__construct(sprintf(
                    'Trying to map to "%s", but it is an interface, not a class. If you want to map to an interface, you need to add the attribute "%s" to the interface."',
                    $class,
                    InheritanceMap::class
                ));
            } else {
                parent::__construct(sprintf(
                    'Trying to map to "%s", but it is not a class.',
                    $class,
                ));
            }
        } catch (\ReflectionException) {
            parent::__construct(sprintf(
                'The name "%s" is not a valid class.',
                $class
            ));
        }
    }
}
