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

namespace Rekalogika\Mapper\Tests\UnitTest\Transformer;

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\PropertyAccessLite\PropertyAccessLite;
use Rekalogika\Mapper\Tests\Fixtures\AccessMethods\ObjectWithVariousAccessMethods;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;

class PropertyAccessLiteTest extends TestCase
{
    public function testGetterSetter(): void
    {
        $accessor = new PropertyAccessLite();
        $object = new ObjectWithVariousAccessMethods();
        $object2 = $object;

        $accessor->setValue($object, 'publicProperty', 'foo');
        $accessor->setValue($object, 'privateProperty', 'foo');

        $this->assertSame('foo', $accessor->getValue($object, 'publicProperty'));
        $this->assertSame('foo', $accessor->getValue($object, 'privateProperty'));

        $this->assertTrue($object2->publicPropertySetterAccessed);
        $this->assertTrue($object2->publicPropertyGetterAccessed);
    }

    public function testGetPrivateProperty(): void
    {
        $accessor = new PropertyAccessLite();
        $object = new ObjectWithVariousAccessMethods();

        $this->expectException(NoSuchPropertyException::class);

        $accessor->getValue($object, 'privatePropertyWithoutGetterSetter');
    }

    public function testSetPrivateProperty(): void
    {
        $accessor = new PropertyAccessLite();
        $object = new ObjectWithVariousAccessMethods();

        $this->expectException(NoSuchPropertyException::class);

        $accessor->setValue($object, 'privatePropertyWithoutGetterSetter', 'foo');
    }

    public function testPublic(): void
    {
        $accessor = new PropertyAccessLite();
        $object = new ObjectWithVariousAccessMethods();

        $accessor->setValue($object, 'publicPropertyWithoutGetterSetter', 'foo');

        $this->assertSame('foo', $accessor->getValue($object, 'publicPropertyWithoutGetterSetter'));
    }

    public function testGetUnsetPublicProperty(): void
    {
        $accessor = new PropertyAccessLite();
        $object = new ObjectWithVariousAccessMethods();
        $this->expectException(UninitializedPropertyException::class);
        $accessor->getValue($object, 'unsetPublicProperty');
    }

    public function testGetUnsetGetter(): void
    {
        $accessor = new PropertyAccessLite();
        $object = new ObjectWithVariousAccessMethods();
        $this->expectException(UninitializedPropertyException::class);
        $accessor->getValue($object, 'unsetPrivatePropertyWithGetter');
    }

    public function testGetMissingProperty(): void
    {
        $accessor = new PropertyAccessLite();
        $object = new ObjectWithVariousAccessMethods();
        $this->expectException(NoSuchPropertyException::class);
        $accessor->getValue($object, 'missingProperty');
    }

    public function testSetMissingProperty(): void
    {
        $accessor = new PropertyAccessLite();
        $object = new ObjectWithVariousAccessMethods();
        $this->expectException(NoSuchPropertyException::class);
        $accessor->setValue($object, 'missingProperty', 'foo');
    }
}
