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

use Rekalogika\Mapper\Attribute\DateTimeOptions;
use Rekalogika\Mapper\Attribute\Map;

final class Chapter2Dto
{
    /**
     * @var list<\DateTimeInterface>
     */
    #[DateTimeOptions(timeZone: 'Asia/Jakarta')]
    #[Map(property: 'book.publicationDates')]
    public array $bookPublicationDates = [];
}
