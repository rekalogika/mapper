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
use Rekalogika\Mapper\Tests\Fixtures\ArrayLike\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithArraySetterDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithVariadicArrayConstructorDto;
use Rekalogika\Mapper\Tests\Fixtures\ArrayLikeDto\ObjectWithVariadicArraySetterDto;
use Rekalogika\Mapper\Tests\Fixtures\ScalarDto\ObjectWithScalarPropertiesDto;

class ArrayGetterSetterTest extends FrameworkTestCase
{
    public function testSetter(): void
    {
        $source = new ObjectWithArrayProperty();
        $target = $this->mapper->map($source, ObjectWithArraySetterDto::class);

        $this->assertNotNull($target->getProperty());
        $this->assertCount(3, $target->getProperty() ?? []);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[0]);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[1]);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[2]);
    }

    public function testVariadicSetter(): void
    {
        $source = new ObjectWithArrayProperty();
        $target = $this->mapper->map($source, ObjectWithVariadicArraySetterDto::class);

        $this->assertNotNull($target->getProperty());
        $this->assertCount(3, $target->getProperty() ?? []);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[0]);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[1]);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[2]);
    }

    public function testVariadicConstructor(): void
    {
        $source = new ObjectWithArrayProperty();
        $target = $this->mapper->map($source, ObjectWithVariadicArrayConstructorDto::class);

        $this->assertNotNull($target->getProperty());
        $this->assertCount(3, $target->getProperty() ?? []);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[0]);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[1]);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertInstanceOf(ObjectWithScalarPropertiesDto::class, $target->getProperty()[2]);
    }
}
