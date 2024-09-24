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

namespace Rekalogika\Mapper\Tests\Fixtures\MapPropertyPath;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class Shelf
{
    private ?Library $library = null;

    /**
     * @var Collection<int,Book>
     */
    private Collection $books;

    private ?int $number = null;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): void
    {
        $this->number = $number;
    }

    public function getLibrary(): ?Library
    {
        return $this->library;
    }

    public function setLibrary(?Library $library): void
    {
        $this->library = $library;
    }

    /**
     * @return Collection<int,Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): void
    {
        $this->books->add($book);
        $book->setShelf($this);
    }

    public function removeBook(Book $book): void
    {
        $this->books->removeElement($book);

        if ($book->getShelf() === $this) {
            $book->setShelf(null);
        }
    }
}
