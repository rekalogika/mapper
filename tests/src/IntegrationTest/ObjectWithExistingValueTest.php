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
use Rekalogika\Mapper\Tests\Fixtures\ObjectWithExistingValue\RootObject;
use Rekalogika\Mapper\Tests\Fixtures\ObjectWithExistingValueDto\FurtherInnerObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectWithExistingValueDto\InnerObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectWithExistingValueDto\RootObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\ObjectWithExistingValueDto\RootObjectWithoutSetterDto;

class ObjectWithExistingValueTest extends FrameworkTestCase
{
    public function testObjectWithExistingValueDtoToObject(): void
    {
        $dto = new RootObjectDto();
        $dto->id = 'id';
        $dto->innerObject = new InnerObjectDto();
        $dto->innerObject->property = 'foo';
        $dto->innerObject->furtherInnerObject = new FurtherInnerObjectDto();
        $dto->innerObject->furtherInnerObject->property = 'bar';

        $object = $this->mapper->map($dto, RootObject::class);

        $this->assertSame('id', $object->getId());
        $this->assertSame('foo', $object->getInnerObject()->getProperty());
        $this->assertSame('bar', $object->getInnerObject()->getFurtherInnerObject()->getProperty());
    }

    public function testObjectWithExistingValueDtoToObjectPreinitialized(): void
    {
        $dto = new RootObjectDto();
        $dto->id = 'id';
        $dto->innerObject = new InnerObjectDto();
        $dto->innerObject->property = 'foo';
        $dto->innerObject->furtherInnerObject = new FurtherInnerObjectDto();
        $dto->innerObject->furtherInnerObject->property = 'bar';

        $object = new RootObject();

        $this->mapper->map($dto, $object);

        $this->assertSame('id', $object->getId());
        $this->assertSame('foo', $object->getInnerObject()->getProperty());
        $this->assertSame('bar', $object->getInnerObject()->getFurtherInnerObject()->getProperty());
    }

    public function testObjectWithExistingValueWithoutTargetSetterDto(): void
    {
        $dto = new RootObjectWithoutSetterDto();
        $dto->id = 'id';
        $dto->getInnerObject()->property = 'foo';
        $dto->getInnerObject()->getFurtherInnerObject()->property = 'bar';

        $object = new RootObject();

        $this->mapper->map($dto, $object);

        $this->assertSame('id', $object->getId());
        $this->assertSame('foo', $object->getInnerObject()->getProperty());
        $this->assertSame('bar', $object->getInnerObject()->getFurtherInnerObject()->getProperty());
    }
}
