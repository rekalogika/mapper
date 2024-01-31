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

namespace Rekalogika\Mapper\Tests\UnitTest\Model;

use PHPUnit\Framework\TestCase;
use Rekalogika\Mapper\Transformer\Model\ObjectStorage;
use Rekalogika\Mapper\Transformer\Model\SplObjectStorageWrapper;

class ArrayLikeObjectTest extends TestCase
{
    public function testSplObjectStorageWrapper(): void
    {
        $object = new SplObjectStorageWrapper(new \SplObjectStorage());

        $key1 = new \stdClass();
        /** @psalm-suppress InvalidArgument */
        $object[$key1] = 'key1';

        $key2 = new \stdClass();
        /** @psalm-suppress InvalidArgument */
        $object[$key2] = 'key2';

        $this->assertCount(2, $object);
        $this->assertTrue(isset($object[$key1]));
        $this->assertTrue(isset($object[$key2]));
        $this->assertEquals('key1', $object[$key1]);
        $this->assertEquals('key2', $object[$key2]);

        foreach ($object as $key => $value) {
            $this->assertInstanceOf(\stdClass::class, $key);
            $this->assertIsString($value);
        }
    }

    public function testObjectStorage(): void
    {
        /**
         * @var ObjectStorage<bool|float|int|object|string|null,string>
         */
        $object = new ObjectStorage();

        $objectKey = new \stdClass();
        /** @psalm-suppress InvalidArgument */
        $object[$objectKey] = 'objectKey';

        $stringKey = 'stringKey';
        $object[$stringKey] = 'stringKey';

        $intKey = 1;
        $object[$intKey] = 'intKey';

        $boolKey = true;
        $object[$boolKey] = 'boolKey';

        $floatKey  = 1.1;
        $object[$floatKey] = 'floatKey';

        $this->assertCount(5, $object);
        $this->assertTrue(isset($object[$objectKey]));
        $this->assertTrue(isset($object[$stringKey]));
        $this->assertEquals('objectKey', $object[$objectKey]);
        $this->assertEquals('stringKey', $object[$stringKey]);

        foreach ($object as $key => $value) {
            switch ($value) {
                case 'objectKey':
                    $this->assertInstanceOf(\stdClass::class, $key);
                    break;
                case 'stringKey':
                    $this->assertIsString($key);
                    break;
                case 'intKey':
                    $this->assertIsInt($key);
                    break;
                case 'boolKey':
                    $this->assertIsBool($key);
                    break;
                case 'floatKey':
                    $this->assertIsFloat($key);
                    break;
            }
        }
    }
}
