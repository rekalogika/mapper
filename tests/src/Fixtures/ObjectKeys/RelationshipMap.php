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

class RelationshipMap
{
    /** @var list<Person> */
    private array $members = [];

    /** @var \ArrayAccess<Person,Person> */
    private \ArrayAccess $spouseMap;

    /** @var \ArrayAccess<Person,Person> */
    private \ArrayAccess $childToFatherMap;

    /** @var \ArrayAccess<Person,Person> */
    private \ArrayAccess $childToMotherMap;

    public function __construct()
    {
        /** @var \SplObjectStorage<Person,Person> */
        $spouseMap = new \SplObjectStorage();
        $this->spouseMap = $spouseMap;

        /** @var \SplObjectStorage<Person,Person> */
        $childToFatherMap = new \SplObjectStorage();
        $this->childToFatherMap = $childToFatherMap;

        /** @var \SplObjectStorage<Person,Person> */
        $childToMotherMap = new \SplObjectStorage();
        $this->childToMotherMap = $childToMotherMap;
    }

    public static function create(): self
    {
        $map = new self();

        $john = new Person('John', Gender::Male, new \DateTimeImmutable('1980-01-01'));
        $jane = new Person('Jane', Gender::Female, new \DateTimeImmutable('1982-03-03'));
        $james = new Person('James', Gender::Male, new \DateTimeImmutable('2010-05-05'));
        $jill = new Person('Jill', Gender::Female, new \DateTimeImmutable('2013-07-07'));

        $map->members = [$john, $jane, $james, $jill];

        $map->spouseMap[$john] = $jane;
        $map->spouseMap[$jane] = $john;

        $map->childToFatherMap[$james] = $john;
        $map->childToMotherMap[$james] = $jane;

        $map->childToFatherMap[$jill] = $john;
        $map->childToMotherMap[$jill] = $jane;

        return $map;
    }

    /**
     * @return \ArrayAccess<Person,Person>
     */
    public function getSpouseMap(): \ArrayAccess
    {
        return $this->spouseMap;
    }

    /**
     * @return \ArrayAccess<Person,Person>
     */
    public function getChildToFatherMap(): \ArrayAccess
    {
        return $this->childToFatherMap;
    }

    /**
     * @return \ArrayAccess<Person,Person>
     */
    public function getChildToMotherMap(): \ArrayAccess
    {
        return $this->childToMotherMap;
    }

    /**
     * @return list<Person>
     */
    public function getMembers(): array
    {
        return $this->members;
    }
}
