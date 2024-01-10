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
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithBoolPropertiesDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithFloatPropertiesDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithIntPropertiesDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithStringPropertiesDto;

class ScalarPropertiesMappingTest extends AbstractIntegrationTest
{
    public function testScalarIdentity(): void
    {
        $class = new ObjectWithScalarProperties();
        $dto = $this->mapper->map($class, ObjectWithScalarPropertiesDto::class);

        $this->assertEquals($class->a, $dto->a);
        $this->assertEquals($class->b, $dto->b);
        $this->assertEquals($class->c, $dto->c);
        $this->assertEquals($class->d, $dto->d);
    }

    public function testScalarToInt(): void
    {
        $class = new ObjectWithScalarProperties();
        $dto = $this->mapper->map($class, ObjectWithIntPropertiesDto::class);

        $this->assertEquals(1, $dto->a);
        $this->assertEquals(0, $dto->b);
        $this->assertEquals(1, $dto->c);
        $this->assertEquals(1, $dto->d);
    }

    public function testScalarToString(): void
    {
        $class = new ObjectWithScalarProperties();
        $dto = $this->mapper->map($class, ObjectWithStringPropertiesDto::class);

        $this->assertEquals('1', $dto->a);
        $this->assertEquals('string', $dto->b);
        $this->assertEquals('1', $dto->c);
        $this->assertEquals('1.1', $dto->d);
    }

    public function testScalarToBool(): void
    {
        $class = new ObjectWithScalarProperties();
        $dto = $this->mapper->map($class, ObjectWithBoolPropertiesDto::class);

        $this->assertEquals(true, $dto->a);
        $this->assertEquals(true, $dto->b);
        $this->assertEquals(true, $dto->c);
        $this->assertEquals(true, $dto->d);
    }

    public function testScalarToFloat(): void
    {
        $class = new ObjectWithScalarProperties();
        $dto = $this->mapper->map($class, ObjectWithFloatPropertiesDto::class);

        $this->assertEquals(1.0, $dto->a);
        $this->assertEquals(0.0, $dto->b);
        $this->assertEquals(1.0, $dto->c);
        $this->assertEquals(1.1, $dto->d);
    }
}
