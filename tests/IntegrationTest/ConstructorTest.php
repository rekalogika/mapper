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
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithConstructorAndMoreArgumentDto;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithConstructorAndPropertiesDto;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithConstructorDto;
use Rekalogika\Mapper\Tests\Fixtures\Constructor\ObjectWithPrivateConstructorDto;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarProperties;
use Rekalogika\Mapper\Tests\Fixtures\Scalar\ObjectWithScalarPropertiesAndAdditionalNullProperty;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\IncompleteConstructorArgument;
use Rekalogika\Mapper\Transformer\Exception\InstantiationFailureException;

class ConstructorTest extends AbstractIntegrationTest
{
    public function testConstructor(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectWithConstructorDto::class);

        $this->assertSame(1, $target->getA());
        $this->assertSame('string', $target->getB());
        $this->assertTrue($target->isC());
        $this->assertSame(1.1, $target->getD());
    }

    public function testPrivateConstructor(): void
    {
        $source = new ObjectWithScalarProperties();
        $this->expectException(ClassNotInstantiableException::class);
        $this->mapper->map($source, ObjectWithPrivateConstructorDto::class);
    }

    public function testConstructorAndProperties(): void
    {
        $source = new ObjectWithScalarProperties();
        $target = $this->mapper->map($source, ObjectWithConstructorAndPropertiesDto::class);

        $this->assertSame(1, $target->getA());
        $this->assertSame('string', $target->getB());
        $this->assertTrue($target->isC());
        $this->assertSame(1.1, $target->getD());
    }

    public function testMissingSourceProperty(): void
    {
        $this->expectException(IncompleteConstructorArgument::class);
        $source = new ObjectWithScalarProperties();
        $this->mapper->map($source, ObjectWithConstructorAndMoreArgumentDto::class);
    }

    public function testNullSourcePropertyAndNotNullTargetProperty(): void
    {
        $this->expectException(InstantiationFailureException::class);
        $source = new ObjectWithScalarPropertiesAndAdditionalNullProperty();
        $this->mapper->map($source, ObjectWithConstructorAndMoreArgumentDto::class);
    }
}
