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

class ObjectWithDateTimeCollection
{
    /**
     * @var Collection<int,\DateTimeInterface>
     */
    private Collection $datetimes;

    public function __construct()
    {
        /**
         * @psalm-suppress MixedAssignment
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        $this->datetimes = new ArrayCollection();
        $this->datetimes->add(new \DateTimeImmutable('2024-01-01 00:00:00', new \DateTimeZone('UTC')));
        $this->datetimes->add(new \DateTimeImmutable('2024-02-01 00:00:00', new \DateTimeZone('UTC')));
        $this->datetimes->add(new \DateTimeImmutable('2024-03-01 00:00:00', new \DateTimeZone('UTC')));
    }

    /**
     * @return Collection<int,\DateTimeInterface>
     */
    public function getDatetimes(): Collection
    {
        return $this->datetimes;
    }
}
