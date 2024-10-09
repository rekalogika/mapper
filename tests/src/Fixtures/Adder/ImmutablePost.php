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

namespace Rekalogika\Mapper\Tests\Fixtures\Adder;

readonly class ImmutablePost
{
    /**
     * @param array<int,Comment> $comments
     */
    public function __construct(
        private array $comments = [],
        private string $content = '',
    ) {}

    /**
     * @return array<int,Comment>
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        $comments = $this->comments;
        $comments[] = $comment;

        return new self($comments, $this->content);
    }

    public function removeComment(Comment $comment): self
    {
        $comments = $this->comments;
        $key = array_search($comment, $comments, true);

        if ($key !== false) {
            unset($comments[$key]);
        }

        return new self($comments, $this->content);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        return new self($this->comments, $content);
    }
}
