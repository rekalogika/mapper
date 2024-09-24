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

final class Book
{
    private ?Shelf $shelf = null;

    /**
     * @var Collection<int,Chapter>
     */
    private readonly Collection $chapters;

    public function __construct()
    {
        $this->chapters = new ArrayCollection();
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
     * @return Collection<int,Chapter|Section>
     */
    public function getParts(): Collection
    {
        return new ArrayCollection();
    }
}
