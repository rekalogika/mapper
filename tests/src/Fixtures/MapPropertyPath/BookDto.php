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

use Rekalogika\Mapper\Attribute\Map;

class BookDto
{
    #[Map(property: 'shelf.library.name')]
    public ?string $libraryName = null;

    #[Map(property: 'shelf.number')]
    public ?int $shelfNumber = null;

    /**
     * @var list<ChapterDto>
     */
    #[Map(property: 'shelf.books[0].chapters')]
    public array $sections = [];
}
