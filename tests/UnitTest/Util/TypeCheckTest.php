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

namespace Rekalogika\Mapper\Tests\UnitTest\Util;

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeBackedEnum;
use Rekalogika\Mapper\Tests\Fixtures\EnumAndStringable\SomeEnum;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;

class TypeCheckTest extends TestCase
{
    public function testCheck(): void
    {
        $this->assertTrue(TypeCheck::nameExists(\DateTimeImmutable::class));
        $this->assertTrue(TypeCheck::nameExists(\DateTimeInterface::class));
        $this->assertTrue(TypeCheck::nameExists(SomeEnum::class));
        $this->assertTrue(TypeCheck::nameExists(SomeBackedEnum::class));
        $this->assertFalse(TypeCheck::nameExists('FooBar'));

        $this->assertTrue(TypeCheck::isInt(TypeFactory::int()));
        $this->assertTrue(TypeCheck::isFloat(TypeFactory::float()));
        $this->assertTrue(TypeCheck::isString(TypeFactory::string()));
        $this->assertTrue(TypeCheck::isBool(TypeFactory::bool()));
        $this->assertTrue(TypeCheck::isArray(TypeFactory::array()));
        $this->assertTrue(TypeCheck::isObject(TypeFactory::objectOfClass(\DateTime::class)));
        $this->assertTrue(TypeCheck::isResource(TypeFactory::resource()));
        $this->assertTrue(TypeCheck::isNull(TypeFactory::null()));

        $this->assertTrue(TypeCheck::isScalar(TypeFactory::int()));
        $this->assertTrue(TypeCheck::isScalar(TypeFactory::float()));
        $this->assertTrue(TypeCheck::isScalar(TypeFactory::string()));
        $this->assertTrue(TypeCheck::isScalar(TypeFactory::bool()));

        $this->assertTrue(TypeCheck::isSomewhatIdentical(
            TypeFactory::int(),
            TypeFactory::int(),
        ));
        $this->assertTrue(TypeCheck::isSomewhatIdentical(
            TypeFactory::float(),
            TypeFactory::float(),
        ));
        $this->assertTrue(TypeCheck::isSomewhatIdentical(
            TypeFactory::string(),
            TypeFactory::string(),
        ));
        $this->assertTrue(TypeCheck::isSomewhatIdentical(
            TypeFactory::bool(),
            TypeFactory::bool(),
        ));
        $this->assertTrue(TypeCheck::isSomewhatIdentical(
            TypeFactory::array(),
            TypeFactory::array(),
        ));
        $this->assertTrue(TypeCheck::isSomewhatIdentical(
            TypeFactory::objectOfClass(\DateTime::class),
            TypeFactory::objectOfClass(\DateTime::class),
        ));
        $this->assertFalse(TypeCheck::isSomewhatIdentical(
            TypeFactory::objectOfClass(\DateTime::class),
            TypeFactory::objectOfClass(\DateTimeImmutable::class),
        ));
        $this->assertTrue(TypeCheck::isSomewhatIdentical(
            TypeFactory::resource(),
            TypeFactory::resource(),
        ));
        $this->assertTrue(TypeCheck::isSomewhatIdentical(
            TypeFactory::null(),
            TypeFactory::null(),
        ));

        $this->assertTrue(
            TypeCheck::isVariableInstanceOf(new \DateTime(), TypeFactory::objectOfClass(\DateTime::class)),
        );
        $this->assertTrue(
            TypeCheck::isVariableInstanceOf(new \DateTime(), TypeFactory::object()),
        );
    }
}
