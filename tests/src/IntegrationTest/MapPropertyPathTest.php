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
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPath\SomeAttribute;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPathDto\Book2Dto;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPathDto\BookDto;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPathDto\Chapter2Dto;
use Rekalogika\Mapper\Tests\Fixtures\MapPropertyPathDto\ChapterDto;
use Rekalogika\Mapper\Transformer\Exception\PropertyPathAwarePropertyInfoExtractorException;
use Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractor\AttributesExtractor;
use Rekalogika\Mapper\Transformer\MetadataUtil\DynamicPropertiesDeterminer;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyAccessInfoExtractor;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyPathMetadataFactory;
use Rekalogika\Mapper\Transformer\MetadataUtil\UnalterableDeterminer;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

class MapPropertyPathTest extends FrameworkTestCase
{
    /**
     * @param class-string $class
     * @param list<Type>|class-string<ExceptionInterface> $expectedTypes
     * @param list<object> $expectedAttributes
     * @dataProvider propertyPathMetadataFactoryDataProvider
     */
    public function testPropertyPathMetadataFactory(
        string $class,
        string $path,
        array|string $expectedTypes,
        array $expectedAttributes = [],
    ): void {
        if (\is_string($expectedTypes)) {
            $this->expectException($expectedTypes);
        }

        $propertyTypeExtractor = $this
            ->get(PropertyTypeExtractorInterface::class);

        $propertyWriteInfoExtractor = $this
            ->get(PropertyWriteInfoExtractorInterface::class);

        $propertyReadInfoExtractor = $this
            ->get(PropertyReadInfoExtractorInterface::class);

        $propertyListExtractor = $this
            ->get(PropertyListExtractorInterface::class);

        $propertyAccessInfoExtractor = new PropertyAccessInfoExtractor(
            propertyReadInfoExtractor: $propertyReadInfoExtractor,
            propertyWriteInfoExtractor: $propertyWriteInfoExtractor,
        );

        $attributesExtractor = new AttributesExtractor(
            propertyAccessInfoExtractor: $propertyAccessInfoExtractor,
        );

        $dynamicPropertiesDeterminer = new DynamicPropertiesDeterminer();

        $unalterableDeterminer = new UnalterableDeterminer(
            propertyListExtractor: $propertyListExtractor,
            propertyAccessInfoExtractor: $propertyAccessInfoExtractor,
            dynamicPropertiesDeterminer: $dynamicPropertiesDeterminer,
            attributesExtractor: $attributesExtractor,
            propertyTypeExtractor: $propertyTypeExtractor,
        );

        $propertyPathAwarePropertyTypeExtractor = new PropertyPathMetadataFactory(
            propertyTypeExtractor: $propertyTypeExtractor,
            propertyAccessInfoExtractor: $propertyAccessInfoExtractor,
            attributesExtractor: $attributesExtractor,
            unalterableDeterminer: $unalterableDeterminer,
        );

        $chapter = new Chapter();

        $book = new Book();
        $book->addChapter($chapter);

        $shelf = new Shelf();
        $shelf->addBook($book);

        $library = new Library();
        $library->addShelf($shelf);

        $metadata = $propertyPathAwarePropertyTypeExtractor
            ->createPropertyMetadata($class, $path);

        $types = $metadata->getTypes();
        $attributes = $metadata->getAttributes();

        $this->assertEquals($expectedTypes, $types);
        $this->assertEquals($expectedAttributes, $attributes->toArray());
    }

    /**
     * @return iterable<int|string,array{0:class-string,1:string,2:list<Type>|class-string<ExceptionInterface>,3?:list<object>}>
     */
    public static function propertyPathMetadataFactoryDataProvider(): iterable
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
            [
                new SomeAttribute('chapter-book'),
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
            [
                new SomeAttribute('book-chapters'),
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
            [
                new SomeAttribute('shelf-library'),
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
            [
                new SomeAttribute('chapter-book'),
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
            [
                new SomeAttribute('book-chapters'),
            ],
        ];

        yield [
            Chapter::class,
            'book.shelf.library.foo',
            PropertyPathAwarePropertyInfoExtractorException::class,
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
        $book->addPublicationDate('05/20/2024 00:00-05');
        $book->addPublicationDate('05/21/2024 00:00-06');
        $book->addPublicationDate('05/22/2024 00:00-07');

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

    public function testInvalid(): void
    {
        $this->expectException(PropertyPathAwarePropertyInfoExtractorException::class);
        $book = $this->createBook();
        $this->mapper->map($book, Book2Dto::class);
    }

    public function testMapInConstructor(): void
    {
        $this->markTestSkipped('Revisit this test later');

        // $book = $this->createBook();
        // $bookWithMapInConstructorDto = $this->mapper->map($book, BookWithMapInConstructorDto::class);

        // $this->assertEquals('The Library', $bookWithMapInConstructorDto->getLibraryName());
        // $this->assertEquals(1, $bookWithMapInConstructorDto->getShelfNumber());
        // $this->assertCount(3, $bookWithMapInConstructorDto->getSections());
        // $this->assertEquals('Chapter 1', $bookWithMapInConstructorDto->getSections()[0]->title);
        // $this->assertEquals('Chapter 2', $bookWithMapInConstructorDto->getSections()[1]->title);
        // $this->assertEquals('Chapter 3', $bookWithMapInConstructorDto->getSections()[2]->title);
    }

    public function testMapInUnpromotedConstructor(): void
    {
        $this->markTestSkipped('Revisit this test later');

        // $book = $this->createBook();
        // $bookWithMapInConstructorDto = $this->mapper->map($book, BookWithMapInUnpromotedConstructorDto::class);

        // $this->assertEquals('The Library', $bookWithMapInConstructorDto->getLibraryName());
        // $this->assertEquals(1, $bookWithMapInConstructorDto->getShelfNumber());
        // $this->assertCount(3, $bookWithMapInConstructorDto->getSections());
        // $this->assertEquals('Chapter 1', $bookWithMapInConstructorDto->getSections()[0]->title);
        // $this->assertEquals('Chapter 2', $bookWithMapInConstructorDto->getSections()[1]->title);
        // $this->assertEquals('Chapter 3', $bookWithMapInConstructorDto->getSections()[2]->title);
    }

    public function testAttributeWithCollectionTypes(): void
    {
        $book = $this->createBook();
        $chapter = $book->getChapters()->first();
        $this->assertInstanceOf(Chapter::class, $chapter);
        $target = $this->mapper->map($chapter, Chapter2Dto::class);

        $this->assertCount(3, $target->bookPublicationDates);
        $this->assertContainsOnlyInstancesOf(\DateTimeInterface::class, $target->bookPublicationDates);

        foreach ($target->bookPublicationDates as $bookPublicationDate) {
            $this->assertEquals('Asia/Jakarta', $bookPublicationDate->getTimezone()->getName());
        }

        $expected = [
            '2024-05-20 07:00:05 Asia/Jakarta',
            '2024-05-21 07:00:06 Asia/Jakarta',
            '2024-05-22 07:00:07 Asia/Jakarta',
        ];

        $actual = array_map(
            static fn(\DateTimeInterface $dateTime): string => $dateTime->format('Y-m-d H:i:s e'),
            $target->bookPublicationDates,
        );

        $this->assertEquals($expected, $actual);
    }
}
