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
use Rekalogika\Mapper\Attribute\DateTimeOptions;

final class Book
{
    private ?Shelf $shelf = null;

    /**
     * @var Collection<int,Chapter>
     */
    #[SomeAttribute('book-chapters')]
    private readonly Collection $chapters;

    /**
     * @var Collection<int,string>
     */
    #[DateTimeOptions(format: 'm/d/Y H:i-s')]
    private readonly Collection $publicationDates;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
        $this->publicationDates = new ArrayCollection();
    }

    public function getShelf(): ?Shelf
    {
        return $this->shelf;
    }

    public function setShelf(?Shelf $shelf): void
    {
        $this->shelf = $shelf;
    }

    /**
     * @return Collection<int,Chapter>
     */
    public function getChapters(): Collection
    {
        return $this->chapters;
    }

    public function addChapter(Chapter $chapter): void
    {
        $this->chapters->add($chapter);
        $chapter->setBook($this);
    }

    public function removeChapter(Chapter $chapter): void
    {
        $this->chapters->removeElement($chapter);

        if ($chapter->getBook() === $this) {
            $chapter->setBook(null);
        }
    }

    /**
     * @return Collection<int,string>
     */
    public function getPublicationDates(): Collection
    {
        return $this->publicationDates;
    }

    public function addPublicationDate(string $publicationDate): void
    {
        $this->publicationDates->add($publicationDate);
    }

    public function removePublicationDate(string $publicationDate): void
    {
        $this->publicationDates->removeElement($publicationDate);
    }

    /**
     * @return Collection<int,Chapter|Section>
     */
    public function getParts(): Collection
    {
        return new ArrayCollection();
    }
}
