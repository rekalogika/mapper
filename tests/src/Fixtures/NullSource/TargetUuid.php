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

namespace Rekalogika\Mapper\Tests\Fixtures\NullSource;

use Symfony\Component\Uid\Uuid;

class TargetUuid
{
    private readonly Uuid $property;

    public function __construct()
    {
        $this->property = Uuid::v7();
    }

    public function getProperty(): Uuid
    {
        return $this->property;
    }
}
