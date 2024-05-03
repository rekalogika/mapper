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

namespace Rekalogika\Mapper\Tests\Fixtures\Uid;

use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class ObjectWithUids
{
    public Uuid $uuid;
    public Ulid $ulid;
    public UuidInterface $ramseyUuid;

    public function __construct()
    {
        $this->uuid = Uuid::fromString('c4e0d7e0-7f1a-4b1e-8e3c-2b4b1b9a0b5a');
        $this->ulid = Ulid::fromString('01F9Z3ZJZ1QJXZJXZJXZJXZJXZ');
        $this->ramseyUuid = RamseyUuid::fromString('c4e0d7e0-7f1a-4b1e-8e3c-2b4b1b9a0b5a');
    }
}
