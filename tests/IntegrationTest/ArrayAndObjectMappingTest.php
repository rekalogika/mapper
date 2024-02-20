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
use Rekalogika\Mapper\Tests\Fixtures\ArrayAndObject\AnotherContainsArray;
use Rekalogika\Mapper\Tests\Fixtures\ArrayAndObject\ContainsArray;
use Rekalogika\Mapper\Tests\Fixtures\ArrayAndObject\ContainsObject;

class ArrayAndObjectMappingTest extends FrameworkTestCase
{
    public function testObjectToArrayAndBack(): void
    {
        $object = ContainsObject::create();
        $dto = $this->mapper->map($object, ContainsArray::class);

        $this->assertEquals(1, $dto->data['a'] ?? null);
        $this->assertEquals('string', $dto->data['b'] ?? null);
        $this->assertEquals(true, $dto->data['c'] ?? null);
        $this->assertEquals(1.1, $dto->data['d'] ?? null);

        $object = $this->mapper->map($dto, ContainsObject::class);

        $this->assertEquals(1, $object->getData()?->a);
        $this->assertEquals('string', $object->getData()?->b);
        $this->assertEquals(true, $object->getData()?->c);
        $this->assertEquals(1.1, $object->getData()?->d);
    }

    public function testArrayToObject(): void
    {
        $objectWithArray = new ContainsArray();
        $objectWithArray->data = [
            'a' => 1,
            'b' => 'string',
            'c' => true,
            'd' => 1.1,
        ];

        $object = $this->mapper->map($objectWithArray, ContainsObject::class);

        $this->assertEquals(1, $object->getData()?->a);
        $this->assertEquals('string', $object->getData()?->b);
        $this->assertEquals(true, $object->getData()?->c);
        $this->assertEquals(1.1, $object->getData()?->d);
    }

    public function testArrayWithIncompletePropertiesToObject(): void
    {
        $objectWithArray = new ContainsArray();
        $objectWithArray->data = [
            'a' => 1,
            'b' => 'string',
        ];

        $object = $this->mapper->map($objectWithArray, ContainsObject::class);

        $this->assertEquals(1, $object->getData()?->a);
        $this->assertEquals('string', $object->getData()?->b);
        $this->assertNull($object->getData()?->c);
        $this->assertNull($object->getData()?->d);
    }

    public function testArrayWithExtraPropertiesToObject(): void
    {
        $objectWithArray = new ContainsArray();
        $objectWithArray->data = [
            'a' => 1,
            'b' => 'string',
            'c' => true,
            'd' => 1.1,
            'e' => 'extra',
        ];

        $object = $this->mapper->map($objectWithArray, ContainsObject::class);

        $this->assertEquals(1, $object->getData()?->a);
        $this->assertEquals('string', $object->getData()?->b);
        $this->assertEquals(true, $object->getData()?->c);
        $this->assertEquals(1.1, $object->getData()?->d);
    }

    public function testArrayToArray(): void
    {
        $objectWithArray = new ContainsArray();
        $objectWithArray->data = [
            'a' => 1,
            'b' => 'string',
            'c' => true,
            'd' => 1.1,
        ];

        $result = $this->mapper->map($objectWithArray, AnotherContainsArray::class);

        $this->assertEquals(1, $result->data['a'] ?? null);
        $this->assertEquals('string', $result->data['b'] ?? null);
        $this->assertEquals(true, $result->data['c'] ?? null);
        $this->assertEquals(1.1, $result->data['d'] ?? null);
    }
}
