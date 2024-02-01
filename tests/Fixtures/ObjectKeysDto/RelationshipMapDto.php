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

namespace Rekalogika\Mapper\Tests\Fixtures\ObjectKeysDto;

class RelationshipMapDto
{
    /** @var list<PersonDto> */
    public array $members = [];

    /** @var \ArrayAccess<PersonDto,PersonDto> */
    public \ArrayAccess $spouseMap;

    /** @var \ArrayAccess<PersonDto,PersonDto> */
    public \ArrayAccess $childToFatherMap;

    /** @var \ArrayAccess<PersonDto,PersonDto> */
    public \ArrayAccess $childToMotherMap;

    public function __construct()
    {
        /** @var \SplObjectStorage<PersonDto,PersonDto> */
        $spouseMap = new \SplObjectStorage();
        $this->spouseMap = $spouseMap;

        /** @var \SplObjectStorage<PersonDto,PersonDto> */
        $childToFatherMap = new \SplObjectStorage();
        $this->childToFatherMap = $childToFatherMap;

        /** @var \SplObjectStorage<PersonDto,PersonDto> */
        $childToMotherMap = new \SplObjectStorage();
        $this->childToMotherMap = $childToMotherMap;
    }
}
