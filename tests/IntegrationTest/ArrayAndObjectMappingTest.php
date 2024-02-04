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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Serializer\DenormalizerContext;
use Rekalogika\Mapper\Serializer\NormalizerContext;
use Rekalogika\Mapper\Tests\Common\AbstractFrameworkTest;
use Rekalogika\Mapper\Tests\Fixtures\ArrayAndObject\ContainingObject;
use Rekalogika\Mapper\Tests\Fixtures\ArrayAndObjectDto\ContainingObjectDto;

class ArrayAndObjectMappingTest extends AbstractFrameworkTest
{
    public function testObjectToArrayAndBack(): void
    {
        $object = ContainingObject::create();
        $dto = $this->mapper->map($object, ContainingObjectDto::class);

        $this->assertEquals(1, $dto->data['a'] ?? null);
        $this->assertEquals('string', $dto->data['b'] ?? null);
        $this->assertEquals(true, $dto->data['c'] ?? null);
        $this->assertEquals(1.1, $dto->data['d'] ?? null);

        $object = $this->mapper->map($dto, ContainingObject::class);

        $this->assertEquals(1, $object->getData()?->a);
        $this->assertEquals('string', $object->getData()?->b);
        $this->assertEquals(true, $object->getData()?->c);
        $this->assertEquals(1.1, $object->getData()?->d);
    }

    public function testObjectToArrayWithSerializerGroups(): void
    {
        $normalizationContext = new NormalizerContext([
            'groups' => ['groupa', 'groupc'],
        ]);
        $context = Context::create($normalizationContext);

        $object = ContainingObject::create();
        $dto = $this->mapper->map($object, ContainingObjectDto::class, $context);

        $this->assertEquals(1, $dto->data['a'] ?? null);
        $this->assertNull($dto->data['b'] ?? null);
        $this->assertEquals(true, $dto->data['c'] ?? null);
        $this->assertNull($dto->data['d'] ?? null);
    }

    public function testArrayToObjectWithSerializerGroups(): void
    {
        $denormalizationContext = new DenormalizerContext([
            'groups' => ['groupa', 'groupc'],
        ]);
        $context = Context::create($denormalizationContext);

        $dto = new ContainingObjectDto();
        $dto->data = [
            'a' => 1,
            'b' => 'string',
            'c' => true,
            'd' => 1.1,
        ];

        $object = $this->mapper->map($dto, ContainingObject::class, $context);

        $this->assertEquals(1, $object->getData()?->a);
        $this->assertNull($object->getData()?->b);
        $this->assertEquals(true, $object->getData()?->c);
        $this->assertNull($object->getData()?->d);
    }
}
