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

namespace Rekalogika\Mapper\Context;

/**
 * Adds additional values to the target object
 */
final readonly class ExtraTargetValues
{
    /**
     * @param array<class-string,array<string,mixed>> $arguments
     */
    public function __construct(
        private array $arguments = [],
    ) {}

    /**
     * @param list<class-string> $classes The class and its parent classes and interfaces.
     * @return array<string,mixed>
     */
    public function getArgumentsForClass(array $classes): array
    {
        $arguments = [];

        foreach ($classes as $class) {
            if (isset($this->arguments[$class])) {
                $arguments = array_merge($arguments, $this->arguments[$class]);
            }
        }

        return $arguments;
    }
}
