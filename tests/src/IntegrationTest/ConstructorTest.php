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

use Rekalogika\Mapper\MainTransformer\Exception\CannotFindTransformerException;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithConstructorAndExtraMandatoryArgumentDto;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithConstructorAndPropertiesDto;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithConstructorDto;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithConstructorWithExtraOptionalArgumentDto;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithMandatoryConstructorThatCannotBeCastFromNullDto;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithPrivateConstructorDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesAndAdditionalNullProperty;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\InstantiationFailureException;

class ConstructorTest extends FrameworkTestCase
{
    public function testConstructor(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectWithConstructorDto::class);

        $this->assertEquals(1, $target->getA());
        $this->assertEquals('string', $target->getB());
        $this->assertTrue($target->isC());
        $this->assertEquals(1.1, $target->getD());
    }

    public function testPrivateConstructor(): void
    {
        $source = new ObjectWithScalarProperties();
        $this->expectException(ClassNotInstantiableException::class);
        $result = $this->mapper->map($source, ObjectWithPrivateConstructorDto::class);
        $this->initialize($result);
    }

    public function testConstructorAndProperties(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectWithConstructorAndPropertiesDto::class);

        $this->assertEquals(1, $target->getA());
        $this->assertEquals('string', $target->getB());
        $this->assertTrue($target->isC());
        $this->assertEquals(1.1, $target->getD());
    }

    public function testMandatoryArgumentWithoutSourceProperty(): void
    {
        $this->expectException(InstantiationFailureException::class);
        $source = new ObjectWithScalarProperties();
        $result = $this->mapper->map($source, ObjectWithConstructorAndExtraMandatoryArgumentDto::class);
        $this->initialize($result);
    }

    public function testOptionalArgumentWithoutSourceProperty(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectWithConstructorWithExtraOptionalArgumentDto::class);

        $this->assertEquals(1, $target->getA());
        $this->assertEquals('string', $target->getB());
        $this->assertTrue($target->isC());
        $this->assertEquals(1.1, $target->getD());
        $this->assertEquals('stringE', $target->getE());
    }

    public function testFromEmptyStdClassToMandatoryArguments(): void
    {
        $source = new \stdClass();
        $target = $this->mapper->map($source, ObjectWithConstructorDto::class);

        $this->assertEquals(0, $target->getA());
        $this->assertEquals('', $target->getB());
        $this->assertFalse($target->isC());
        $this->assertEquals(0.0, $target->getD());
    }

    public function testFromEmptyStdClassToMandatoryArgumentsThatCannotBeCastFromNull(): void
    {
        $this->expectException(CannotFindTransformerException::class);
        $source = new \stdClass();
        $this->mapper->map($source, ObjectWithMandatoryConstructorThatCannotBeCastFromNullDto::class);
    }

    public function testNullSourcePropertyAndNotNullTargetProperty(): void
    {
        $this->expectException(InstantiationFailureException::class);
        $source = new ObjectWithScalarPropertiesAndAdditionalNullProperty();
        $result = $this->mapper->map($source, ObjectWithConstructorAndExtraMandatoryArgumentDto::class);
        $this->initialize($result);
    }
}
