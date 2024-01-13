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
use Rekalogika\Mapper\Tests\Fixtures\Attribute\ObjectWithAttribute;
use Rekalogika\Mapper\Tests\Fixtures\Attribute\SomeAttribute;
use Rekalogika\Mapper\Util\TypeFactory;

class AttributeTest extends AbstractIntegrationTest
{
    public function testAttribute(): void
    {
        $class = ObjectWithAttribute::class;
        $type = TypeFactory::objectOfClass($class);

        $typeStrings = $this->typeResolver->getApplicableTypeStrings($type);

        $this->assertContainsEquals(SomeAttribute::class, $typeStrings);
    }
}
