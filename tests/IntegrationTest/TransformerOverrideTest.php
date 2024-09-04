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

use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Tests\Common\FrameworkTestCase;
use Rekalogika\Mapper\Tests\Fixtures\Override\ObjectWithArrayProperty;
use Rekalogika\Mapper\Tests\Fixtures\Override\ObjectWithArrayPropertyDto;

/**
 * @internal
 */
class TransformerOverrideTest extends FrameworkTestCase
{
    public function testTransformerOverride(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source must be scalar');
        $source = new ObjectWithArrayProperty();
        $target = $this->mapper->map($source, ObjectWithArrayPropertyDto::class);
    }
}
