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

namespace Rekalogika\Mapper\Tests\Fixtures\AdderDto;

class PostDto
{
    /** @var array<int,CommentDto> */
    private array $comments = [];

    private string $content = '';

    /**
     * @return array<int,CommentDto>
     */
    public function getComments(): array
    {
        return $this->comments;
    }

    public function addComment(CommentDto $comment): void
    {
        $this->comments[] = $comment;
    }

    public function removeComment(CommentDto $comment): void
    {
        $key = array_search($comment, $this->comments, true);

        if ($key !== false) {
            unset($this->comments[$key]);
        }
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
