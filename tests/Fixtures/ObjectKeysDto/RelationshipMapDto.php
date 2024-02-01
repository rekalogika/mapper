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

    /** @var ?\ArrayAccess<PersonDto,PersonDto> */
    public ?\ArrayAccess $spouseMap = null;

    /** @var ?\ArrayAccess<PersonDto,PersonDto> */
    public ?\ArrayAccess $childToFatherMap = null;

    /** @var ?\ArrayAccess<PersonDto,PersonDto> */
    public ?\ArrayAccess $childToMotherMap = null;
}
