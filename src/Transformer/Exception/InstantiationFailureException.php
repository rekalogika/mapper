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

use Rekalogika\Mapper\Context\Context;

final class InstantiationFailureException extends NotMappableValueException
{
    /**
     * @param array<int|string,mixed> $constructorArguments
     * @param array<int,string> $unsetSourceProperties
     */
    public function __construct(
        object $source,
        string $targetClass,
        array $constructorArguments,
        array $unsetSourceProperties,
        \Throwable $previous,
        Context $context,
    ) {
        if ($constructorArguments === []) {
            $message = \sprintf(
                'Trying to map the source object of type "%s", but failed to instantiate the target object "%s" with no constructor argument.',
                get_debug_type($source),
                $targetClass,
            );
        } else {
            $message = \sprintf(
                'Trying to map the source object of type "%s", but failed to instantiate the target object "%s" using constructor arguments: %s.',
                get_debug_type($source),
                $targetClass,
                $this->formatConstructorArguments($constructorArguments),
            );
        }

        if ($unsetSourceProperties !== []) {
            $message .= \sprintf(
                ' Note that the following properties are not set in the source object: %s.',
                implode(', ', $unsetSourceProperties),
            );
        }

        $message .= \sprintf(
            ' The original error message was: %s.',
            $previous->getMessage(),
        );

        parent::__construct(
            message: $message,
            previous: $previous,
            context: $context,
        );
    }

    /**
     * @param array<int|string,mixed> $constructorArguments
     */
    private function formatConstructorArguments(array $constructorArguments): string
    {
        $formattedArguments = [];
        /** @var mixed $argumentValue */
        foreach ($constructorArguments as $argumentName => $argumentValue) {
            $formattedArguments[] = \sprintf('%s: %s', $argumentName, get_debug_type($argumentValue));
        }

        return implode(', ', $formattedArguments);
    }
}
