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

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Symfony\Component\PropertyInfo\Type;

final class TypeGuesser
{
    private function __construct()
    {
    }

    public static function guessTypeFromVariable(mixed $variable): Type
    {
        $type = get_debug_type($variable);

        if ($type === 'array') {
            return TypeFactory::array();
        } elseif ($type === 'bool') {
            return TypeFactory::bool();
        } elseif ($type === 'int') {
            return TypeFactory::int();
        } elseif ($type === 'float') {
            return TypeFactory::float();
        } elseif ($type === 'string') {
            return TypeFactory::string();
        } elseif ($type === 'null') {
            return TypeFactory::null();
        }

        if (class_exists($type) || interface_exists($type) || \enum_exists($type)) {
            return TypeFactory::objectOfClass($type);
        }

        if (\str_starts_with($type, 'resource')) {
            return TypeFactory::resource();
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot determine type of variable "%s"',
            get_debug_type($variable),
        ));
    }
}
