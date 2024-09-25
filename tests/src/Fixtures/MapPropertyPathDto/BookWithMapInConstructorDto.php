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

namespace Rekalogika\Mapper\Tests\Fixtures\MapPropertyPathDto;

use Rekalogika\Mapper\Attribute\Map;

final readonly class BookWithMapInConstructorDto
{
    public function __construct(
        #[Map(property: 'shelf.library.name')]
        private ?string $libraryName = null,
        #[Map(property: 'shelf.number')]
        private ?int $shelfNumber = null,

        /**
         * @var list<ChapterDto>
         */
        #[Map(property: 'shelf.books[0].chapters')]
        private array $sections = [],
    ) {}

    public function getLibraryName(): ?string
    {
        return $this->libraryName;
    }

    public function getShelfNumber(): ?int
    {
        return $this->shelfNumber;
    }

    /**
     * @return list<ChapterDto>
     */
    public function getSections(): array
    {
        return $this->sections;
    }
}
