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

namespace Rekalogika\Mapper\MainTransformer\Model;

/**
 * Represents the mapping path. Used for tracing purposes.
 *
 * @immutable
 */
readonly class Path implements \Stringable
{
    private function __construct(
        private string $path = ''
    ) {
    }

    public static function create(): self
    {
        return new self();
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function append(string $index): self
    {
        $path = $this->path;
        $path .= $index;

        return new self($path);
    }
}
