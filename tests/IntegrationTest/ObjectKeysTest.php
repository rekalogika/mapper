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

use Rekalogika\Mapper\Tests\Common\AbstractIntegrationTest;
use Rekalogika\Mapper\Tests\Fixtures\ObjectKeys\RelationshipMap;
use Rekalogika\Mapper\Tests\Fixtures\ObjectKeysDto\RelationshipMapDto;

class ObjectKeysTest extends AbstractIntegrationTest
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

        $this->assertSame($john, $relationshipMapDto->spouseMap[$jane]);
        $this->assertSame($jane, $relationshipMapDto->spouseMap[$john]);

        $this->assertSame($john, $relationshipMapDto->childToFatherMap[$james]);
        $this->assertSame($jane, $relationshipMapDto->childToMotherMap[$james]);

        $this->assertSame($jane, $relationshipMapDto->childToMotherMap[$jill]);
        $this->assertSame($john, $relationshipMapDto->childToFatherMap[$jill]);
    }
}
