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
use Rekalogika\Mapper\Tests\Fixtures\Inheritance\AbstractClass;
use Rekalogika\Mapper\Tests\Fixtures\Inheritance\ConcreteClassA;
use Rekalogika\Mapper\Tests\Fixtures\InheritanceDto\ConcreteClassADto;

/**
 * @internal
 */
class InheritanceReversedTest extends FrameworkTestCase
{
    public function testMapDtoToAbstractClass(): void
    {
        $concreteClassADto = new ConcreteClassADto();
        $concreteClassADto->propertyInA = 'xxpropertyInA';
        $concreteClassADto->propertyInParent = 'xxpropertyInParent';

        $result = $this->mapper->map($concreteClassADto, AbstractClass::class);

        /** @var ConcreteClassA $result */
        $this->assertInstanceOf(ConcreteClassA::class, $result);
        $this->assertSame('xxpropertyInA', $result->propertyInA);
        $this->assertSame('xxpropertyInParent', $result->propertyInParent);
    }
}
