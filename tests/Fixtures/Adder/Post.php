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

class Post
{
    /** @var array<int,Comment> */
    private array $comments = [];

    private string $contents = '';

    /**
     * @return array<int,Comment>
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): void
    {
        $this->comments[] = $comment;
    }

    public function removeComment(Comment $comment): void
    {
        $key = array_search($comment, $this->comments, true);

        if (false !== $key) {
            unset($this->comments[$key]);
        }
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function setContents(string $contents): self
    {
        $this->contents = $contents;

        return $this;
    }
}
