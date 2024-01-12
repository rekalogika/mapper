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

namespace Rekalogika\Mapper\Exception;

class InstantiationFailureException extends NotMappableValueException
{
    /**
     * @param array<string,mixed> $constructorArguments
     */
    public function __construct(
        object $source,
        string $targetClass,
        array $constructorArguments,
        \Throwable $previous
    ) {
        if (count($constructorArguments) === 0) {
            parent::__construct(sprintf(
                'Trying to map the source object of type "%s", but failed to instantiate the target object "%s" with no constructor argument.',
                \get_debug_type($source),
                $targetClass,
            ), 0, $previous);
        } else {
            parent::__construct(sprintf(
                'Trying to map the source object of type "%s", but failed to instantiate the target object "%s" using constructor arguments: %s.',
                \get_debug_type($source),
                $targetClass,
                self::formatConstructorArguments($constructorArguments)
            ), 0, $previous);
        }

    }

    /**
     * @param array<string,mixed> $constructorArguments
     */
    private static function formatConstructorArguments(array $constructorArguments): string
    {
        $formattedArguments = [];
        /** @var mixed $argumentValue */
        foreach ($constructorArguments as $argumentName => $argumentValue) {
            $formattedArguments[] = sprintf('%s: %s', $argumentName, \get_debug_type($argumentValue));
        }

        return implode(', ', $formattedArguments);
    }
}
