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

final class Library
{
    /**
     * @var Collection<int,Shelf>
     */
    private readonly Collection $shelves;

    private ?string $name = null;

    public function __construct()
    {
        $this->shelves = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<int,Shelf>
     */
    public function getShelves(): Collection
    {
        return $this->shelves;
    }

    public function addShelf(Shelf $shelf): void
    {
        $this->shelves->add($shelf);
        $shelf->setLibrary($this);
    }

    public function removeShelf(Shelf $shelf): void
    {
        $this->shelves->removeElement($shelf);

        if ($shelf->getLibrary() === $this) {
            $shelf->setLibrary(null);
        }
    }
}
