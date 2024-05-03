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
use Rekalogika\Mapper\Tests\Fixtures\Uid\ObjectWithStringUids;
use Rekalogika\Mapper\Tests\Fixtures\Uid\ObjectWithUids;
use Rekalogika\Mapper\Tests\Fixtures\UidDto\ObjectWithStringUidsDto;
use Rekalogika\Mapper\Tests\Fixtures\UidDto\ObjectWithUidsDto;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class UidTest extends FrameworkTestCase
{
    public function testUuidToString(): void
    {
        $object = new ObjectWithUids();
        $dto = $this->mapper->map($object, ObjectWithStringUidsDto::class);

        $this->assertEquals('c4e0d7e0-7f1a-4b1e-8e3c-2b4b1b9a0b5a', $dto->uuid);
        $this->assertEquals('01F9Z3ZJZ1QJXZJXZJXZJXZJXZ', $dto->ulid);
        $this->assertEquals('c4e0d7e0-7f1a-4b1e-8e3c-2b4b1b9a0b5a', $dto->ramseyUuid);
    }

    public function testStringToUuid(): void
    {
        $object = new ObjectWithStringUids();
        $dto = $this->mapper->map($object, ObjectWithUidsDto::class);

        $this->assertInstanceOf(ObjectWithUidsDto::class, $dto);
        $this->assertEquals(Uuid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8'), $dto->uuid);
        $this->assertEquals(Ulid::fromString('01F9Z3ZQZJQJZJZJZJZJZJZJZJ'), $dto->ulid);
        $this->assertEquals('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $dto->ramseyUuid?->toString());
    }

    public function testUuidToUuid(): void
    {
        $object = new ObjectWithUids();
        $dto = $this->mapper->map($object, ObjectWithUidsDto::class);

        $this->assertInstanceOf(ObjectWithUidsDto::class, $dto);
        $this->assertEquals(Uuid::fromString('c4e0d7e0-7f1a-4b1e-8e3c-2b4b1b9a0b5a'), $dto->uuid);
        $this->assertEquals(Ulid::fromString('01F9Z3ZJZ1QJXZJXZJXZJXZJXZ'), $dto->ulid);
        $this->assertEquals('c4e0d7e0-7f1a-4b1e-8e3c-2b4b1b9a0b5a', $dto->ramseyUuid?->toString());
    }

}
