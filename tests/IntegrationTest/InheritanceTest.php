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
use Rekalogika\Mapper\Tests\Fixtures\Inheritance\ConcreteClassA;
use Rekalogika\Mapper\Tests\Fixtures\Inheritance\ConcreteClassC;
use Rekalogika\Mapper\Tests\Fixtures\InheritanceDto\AbstractClassDto;
use Rekalogika\Mapper\Tests\Fixtures\InheritanceDto\AbstractClassWithoutMapDto;
use Rekalogika\Mapper\Tests\Fixtures\InheritanceDto\ConcreteClassADto;
use Rekalogika\Mapper\Tests\Fixtures\InheritanceDto\ImplementationADto;
use Rekalogika\Mapper\Tests\Fixtures\InheritanceDto\InterfaceDto;
use Rekalogika\Mapper\Tests\Fixtures\InheritanceDto\InterfaceWithoutMapDto;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\NotAClassException;
use Rekalogika\Mapper\Transformer\Exception\SourceClassNotInInheritanceMapException;

class InheritanceTest extends AbstractIntegrationTest
{
    public function testMapToAbstractClass(): void
    {
        $concreteClassA = new ConcreteClassA();
        $result = $this->mapper->map($concreteClassA, AbstractClassDto::class);

        /** @var ConcreteClassADto $result */

        $this->assertInstanceOf(ConcreteClassADto::class, $result);
        $this->assertSame('propertyInA', $result->propertyInA);
        $this->assertSame('propertyInParent', $result->propertyInParent);
    }

    public function testMapToAbstractClassWithoutMap(): void
    {
        $concreteClassA = new ConcreteClassA();
        $this->expectException(ClassNotInstantiableException::class);
        $result = $this->mapper->map($concreteClassA, AbstractClassWithoutMapDto::class);
    }

    public function testMapToAbstractClassWithMissingSourceClassInMap(): void
    {
        $concreteClassC = new ConcreteClassC();
        $this->expectException(SourceClassNotInInheritanceMapException::class);
        $result = $this->mapper->map($concreteClassC, AbstractClassDto::class);
    }

    public function testMapToInterface(): void
    {
        $concreteClassA = new ConcreteClassA();
        $result = $this->mapper->map($concreteClassA, InterfaceDto::class);

        /** @var ImplementationADto $result */

        $this->assertInstanceOf(ImplementationADto::class, $result);
        $this->assertSame('propertyInA', $result->propertyInA);
    }

    public function testMapToInterfaceWithoutMap(): void
    {
        $concreteClassA = new ConcreteClassA();
        $this->expectException(NotAClassException::class);
        $result = $this->mapper->map($concreteClassA, InterfaceWithoutMapDto::class);
    }

    public function testMapToInterfaceWithMissingSourceClassInMap(): void
    {
        $concreteClassC = new ConcreteClassC();
        $this->expectException(SourceClassNotInInheritanceMapException::class);
        $result = $this->mapper->map($concreteClassC, InterfaceDto::class);
    }
}
