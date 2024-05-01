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
use Rekalogika\Mapper\Tests\Fixtures\PropertyInSetterAndConstructor\ChildObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyInSetterAndConstructor\ChildObjectDto;
use Rekalogika\Mapper\Tests\Fixtures\PropertyInSetterAndConstructor\ParentObject;
use Rekalogika\Mapper\Tests\Fixtures\PropertyInSetterAndConstructor\ParentObjectDto;

class PropertyInSetterAndConstructorTest extends FrameworkTestCase
{
    public function testPropertyInSetterAndConstructor(): void
    {
        $dto = new ParentObjectDto();
        $dto->name = 'dto-name';
        $dto->child = new ChildObjectDto();
        $dto->child->a = 'dto-a';

        $entity = $this->mapper->map($dto, ParentObject::class);

        $this->assertSame('dto-name', $entity->getName());
        $this->assertSame('dto-a', $entity->getChild()->getA());
    }

    public function testPropertyInSetterAndConstructorPreInitialized(): void
    {
        $dto = new ParentObjectDto();
        $dto->name = 'dto-name';
        $dto->child = new ChildObjectDto();
        $dto->child->a = 'dto-a';

        $entity =  new ParentObject('entity-name', new ChildObject('entity-a'));
        $this->mapper->map($dto, $entity);

        $this->assertSame('dto-name', $entity->getName());
        $this->assertSame('dto-a', $entity->getChild()->getA());
    }
}
