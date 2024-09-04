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

namespace Rekalogika\Mapper\Tests\Fixtures\ObjectKeys;

class Person
{
    public function __construct(
        private readonly string $name,
        private readonly Gender $gender,
        private readonly \DateTimeInterface $birthDate,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function getBirthDate(): \DateTimeInterface
    {
        return $this->birthDate;
    }
}
