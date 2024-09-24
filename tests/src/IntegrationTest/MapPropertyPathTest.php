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

use Doctrine\Common\Collections\Collection;
use Rekalogika\Mapper\Exception\ExceptionInterface;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPath\Book;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPath\Chapter;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPath\Library;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPath\Section;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPath\Shelf;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPathDto\BookDto;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPathDto\ChapterDto;
use Rekalogika\Mapper\Transformer\Exception\PropertyPathResolverException;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\PropertyPathResolver;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

class MapPropertyPathTest extends FrameworkTestCase
{
    /**
     * @param class-string $class
     * @param list<Type>|class-string<ExceptionInterface> $expected
     * @dataProvider propertyPathResolverDataProvider
     */
    public function testPropertyPathResolver(
        string $class,
        string $path,
        array|string $expected,
    ): void {
        if (\is_string($expected)) {
            $this->expectException($expected);
        }

        $propertyTypeExtractor = $this->get(PropertyTypeExtractorInterface::class);
        $propertyPathResolver = new PropertyPathResolver($propertyTypeExtractor);

        $chapter = new Chapter();

        $book = new Book();
        $book->addChapter($chapter);

        $shelf = new Shelf();
        $shelf->addBook($book);

        $library = new Library();
        $library->addShelf($shelf);

        $type = $propertyPathResolver->resolvePropertyPath($class, $path);

        $this->assertEquals($expected, $type);
    }

    /**
     * @return iterable<int|string,array{class-string,string,list<Type>|class-string<ExceptionInterface>}>
     */
    public static function propertyPathResolverDataProvider(): iterable
    {
        yield [
            Book::class,
            'chapters[0].book',
            [
                new Type(
                    builtinType: 'object',
                    class: Book::class,
                    nullable: true,
                ),
            ],
        ];

        yield [
            Book::class,
            'chapters[0].book.chapters',
            [
                new Type(
                    builtinType: 'object',
                    class: Collection::class,
                    nullable: false,
                    collection: true,
                    collectionKeyType: new Type(
                        builtinType: 'int',
                        nullable: false,
                    ),
                    collectionValueType: new Type(
                        builtinType: 'object',
                        class: Chapter::class,
                        nullable: false,
                    ),
                ),
            ],
        ];

        yield [
            Chapter::class,
            'book.shelf.library',
            [
                new Type(
                    builtinType: 'object',
                    class: Library::class,
                    nullable: true,
                ),
            ],
        ];

        yield [
            Library::class,
            'shelves[1].books[0].chapters[0].book',
            [
                new Type(
                    builtinType: 'object',
                    class: Book::class,
                    nullable: true,
                ),
            ],
        ];

        yield [
            Library::class,
            'shelves[1].books[0].chapters[0]',
            [
                new Type(
                    builtinType: 'object',
                    class: Chapter::class,
                    nullable: false,
                ),
            ],
        ];

        yield [
            Chapter::class,
            'book.shelf.library.foo',
            PropertyPathResolverException::class,
        ];

        yield [
            Book::class,
            'parts[foo]',
            [
                new Type(
                    builtinType: 'object',
                    class: Chapter::class,
                    nullable: false,
                ),
                new Type(
                    builtinType: 'object',
                    class: Section::class,
                    nullable: false,
                ),
            ],
        ];
    }

    private function createBook(): Book
    {
        $chapter1 = new Chapter();
        $chapter1->setTitle('Chapter 1');

        $chapter2 = new Chapter();
        $chapter2->setTitle('Chapter 2');

        $chapter3 = new Chapter();
        $chapter3->setTitle('Chapter 3');

        $book = new Book();
        $book->addChapter($chapter1);
        $book->addChapter($chapter2);
        $book->addChapter($chapter3);

        $shelf = new Shelf();
        $shelf->setNumber(1);
        $shelf->addBook($book);

        $library = new Library();
        $library->setName('The Library');
        $library->addShelf($shelf);

        return $book;
    }

    public function testMapping(): void
    {
        $book = $this->createBook();
        $bookDto = $this->mapper->map($book, BookDto::class);

        $this->assertEquals('The Library', $bookDto->libraryName);
        $this->assertEquals(1, $bookDto->shelfNumber);
        $this->assertCount(3, $bookDto->sections);
        $this->assertEquals('Chapter 1', $bookDto->sections[0]->title);
        $this->assertEquals('Chapter 2', $bookDto->sections[1]->title);
        $this->assertEquals('Chapter 3', $bookDto->sections[2]->title);
    }

    public function testReverseMapping(): void
    {
        $book = $this->createBook();
        $book->getChapters()->clear();

        $bookDto = new BookDto();
        $bookDto->libraryName = 'Some Library';
        $bookDto->shelfNumber = 31337;

        $chapterDto1 = new ChapterDto();
        $chapterDto1->title = 'Chapter 1';
        $bookDto->sections[] = $chapterDto1;

        $chapterDto2 = new ChapterDto();
        $chapterDto2->title = 'Chapter 2';
        $bookDto->sections[] = $chapterDto2;

        $chapterDto3 = new ChapterDto();
        $chapterDto3->title = 'Chapter 3';
        $bookDto->sections[] = $chapterDto3;

        $this->assertEquals('The Library', $book->getShelf()?->getLibrary()?->getName());
        $book = $this->mapper->map($bookDto, $book);
        $this->assertEquals('Some Library', $book->getShelf()?->getLibrary()?->getName());

        $this->assertEquals(31337, $book->getShelf()?->getNumber());
        $this->assertCount(3, $book->getChapters());
        $this->assertEquals('Chapter 1', $book->getChapters()->get(0)?->getTitle());
        $this->assertEquals('Chapter 2', $book->getChapters()->get(1)?->getTitle());
        $this->assertEquals('Chapter 3', $book->getChapters()->get(2)?->getTitle());
    }

}
