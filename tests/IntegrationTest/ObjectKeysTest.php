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

namespace Rekalogika\Mapper\Tests\IntegrationTest;

use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\ObjectKeys\RelationshipMap;
use Rekalogika\Mapper\Tests\Fixtures\ObjectKeysDto\PersonDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectKeysDto\RelationshipMapDto;
use Rekalogika\Mapper\Transformer\Model\HashTable;

class ObjectKeysTest extends FrameworkTestCase
{
    public function testObjectKeys(): void
    {
        $relationshipMap = RelationshipMap::create();
        $relationshipMapDto = $this->mapper->map($relationshipMap, RelationshipMapDto::class);

        $this->assertInstanceOf(RelationshipMapDto::class, $relationshipMapDto);

        $members = $relationshipMapDto->members;
        $all = [];
        foreach ($members as $member) {
            $name = $member->name;
            $this->assertNotNull($name);
            $all[$name] = $member;
        }

        $john = $all['John'];
        $jane = $all['Jane'];
        $james = $all['James'];
        $jill = $all['Jill'];

        $this->assertInstanceOf(PersonDto::class, $john);
        $this->assertInstanceOf(PersonDto::class, $jane);
        $this->assertInstanceOf(PersonDto::class, $james);
        $this->assertInstanceOf(PersonDto::class, $jill);

        $spouseMap = $relationshipMapDto->spouseMap;
        $this->assertNotNull($spouseMap);
        $this->assertInstanceOf(HashTable::class, $spouseMap);

        $childToFatherMap = $relationshipMapDto->childToFatherMap;
        $this->assertNotNull($childToFatherMap);
        $this->assertInstanceOf(HashTable::class, $childToFatherMap);

        $childToMotherMap = $relationshipMapDto->childToMotherMap;
        $this->assertNotNull($childToMotherMap);
        $this->assertInstanceOf(HashTable::class, $childToMotherMap);

        $this->assertSame($john, $spouseMap[$jane]);
        $this->assertSame($jane, $spouseMap[$john]);

        $this->assertSame($john, $childToFatherMap[$james]);
        $this->assertSame($jane, $childToMotherMap[$james]);

        $this->assertSame($jane, $childToMotherMap[$jill]);
        $this->assertSame($john, $childToFatherMap[$jill]);
    }
}
