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

namespace Rekalogika\Mapper\Tests\IntegrationTest;

use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Adder\Comment;
use Rekalogika\Mapper\Tests\Fixtures\Adder\ImmutablePost;
use Rekalogika\Mapper\Tests\Fixtures\Adder\Post;
use Rekalogika\Mapper\Tests\Fixtures\AdderDto\CommentDto;
use Rekalogika\Mapper\Tests\Fixtures\AdderDto\ImmutablePostDto;
use Rekalogika\Mapper\Tests\Fixtures\AdderDto\PostDto;

class AdderTest extends FrameworkTestCase
{
    public function testAdder(): void
    {
        $post = new Post();
        $post->setContent('content');
        $post->addComment(new Comment('comment1'));
        $post->addComment(new Comment('comment2'));
        $post->addComment(new Comment('comment3'));

        $postDto = $this->mapper->map($post, PostDto::class);

        $this->assertSame('content', $postDto->getContent());
        $this->assertCount(3, $postDto->getComments());
        $this->assertSame('comment1', $postDto->getComments()[0]->getContent());
        $this->assertSame('comment2', $postDto->getComments()[1]->getContent());
        $this->assertSame('comment3', $postDto->getComments()[2]->getContent());
    }

    public function testCommentMapping(): void
    {
        $comment = new Comment('foo');
        $commentDto = $this->mapper->map($comment, CommentDto::class);

        $this->assertSame('foo', $commentDto->getContent());
    }

    public function testCommentMappingExisting(): void
    {
        $comment = new Comment('foo');
        $commentDto = new CommentDto('bar');
        $commentDto = $this->mapper->map($comment, $commentDto);

        $this->assertSame('foo', $commentDto->getContent());
    }

    public function testAdderWithExistingValue(): void
    {
        $post = new Post();
        $post->setContent('content');
        $post->addComment(new Comment('new comment 1'));
        $post->addComment(new Comment('new comment 2'));
        $post->addComment(new Comment('new comment 3'));

        $postDto = new PostDto();
        $postDto->setContent('to be replaced');
        $postDto->addComment(new CommentDto('old comment 1'));
        $postDto->addComment(new CommentDto('old comment 2'));

        $postDto = $this->mapper->map($post, $postDto);

        $this->assertSame('content', $postDto->getContent());
        $this->assertCount(5, $postDto->getComments());
        $this->assertSame('old comment 1', $postDto->getComments()[0]->getContent());
        $this->assertSame('old comment 2', $postDto->getComments()[1]->getContent());
        $this->assertSame('new comment 1', $postDto->getComments()[2]->getContent());
        $this->assertSame('new comment 2', $postDto->getComments()[3]->getContent());
        $this->assertSame('new comment 3', $postDto->getComments()[4]->getContent());
    }

    public function testImmutableAdder(): void
    {
        $post = new ImmutablePost(
            content: 'content',
            comments: [
                new Comment('new comment 1'),
                new Comment('new comment 2'),
                new Comment('new comment 3'),
            ],
        );

        $postDto = new ImmutablePostDto(
            content: 'to be replaced',
            comments: [
                new CommentDto('old comment 1'),
                new CommentDto('old comment 2'),
            ],
        );

        $newPostDto = $this->mapper->map($post, $postDto);

        $this->assertNotEquals($postDto, $newPostDto);

        $this->assertSame('content', $newPostDto->getContent());
        $this->assertCount(5, $newPostDto->getComments());

        $this->assertSame('old comment 1', $newPostDto->getComments()[0]->getContent());
        $this->assertSame('old comment 2', $newPostDto->getComments()[1]->getContent());

        $this->assertSame('new comment 1', $newPostDto->getComments()[2]->getContent());
        $this->assertSame('new comment 2', $newPostDto->getComments()[3]->getContent());
        $this->assertSame('new comment 3', $newPostDto->getComments()[4]->getContent());
    }
}
