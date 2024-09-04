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
final readonly class Path implements \Stringable
{
    /**
     * @param list<string> $path
     */
    private function __construct(
        private array $path = []
    ) {}

    public static function create(): self
    {
        return new self();
    }

    #[\Override]
    public function __toString(): string
    {
        $result = '';

        foreach ($this->path as $path) {
            // if path contains '[' or ']'
            if (str_contains($path, '[')) {
                // remove [ and ]
                $path = str_replace(['[', ']'], '', $path);
                $result .= sprintf('[%s]', $path);
            } elseif (str_contains($path, '(')) {
                // remove ( and )
                $path = str_replace(['(', ')'], '', $path);
                $result .= sprintf('(%s)', $path);
            } else {
                $result .= '.'.$path;
            }
        }

        // remove leading dot

        if (str_starts_with($result, '.')) {
            $result = substr($result, 1);
        }

        return $result;
    }

    public function append(string $index): self
    {
        $path = $this->path;
        $path[] = $index;

        return new self($path);
    }

    public function getLast(): ?string
    {
        $lastKey = array_key_last($this->path);

        if (null === $lastKey) {
            return null;
        }

        return $this->path[$lastKey] ?? null;
    }
}
