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
use Rekalogika\Mapper\Tests\Fixtures\InternalClass\ObjectWithInternalClass;
use Rekalogika\Mapper\Tests\Fixtures\InternalClass\ObjectWithInternalClassDto;
use Rekalogika\Mapper\Transformer\Exception\InternalClassUnsupportedException;

class InternalClassTest extends AbstractIntegrationTest
{
    public function testInternalClass(): void
    {
        $object = new ObjectWithInternalClass();
        $this->expectException(InternalClassUnsupportedException::class);
        $this->mapper->map($object, ObjectWithInternalClassDto::class);
    }
}
