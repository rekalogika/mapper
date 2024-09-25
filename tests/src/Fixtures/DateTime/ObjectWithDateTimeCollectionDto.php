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

namespace Rekalogika\Mapper\Tests\Fixtures\DateTime;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Rekalogika\Mapper\Attribute\DateTimeOptions;

class ObjectWithDateTimeCollectionDto
{
    /**
     * @var Collection<int,string>
     */
    #[DateTimeOptions(timeZone: 'Asia/Jakarta', format: 'Y-m-d H:i:s e')]
    public Collection $datetimes;

    public function __construct()
    {
        $this->datetimes = new ArrayCollection();
    }
}
