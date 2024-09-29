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

namespace Rekalogika\Mapper\Tests\Fixtures\PropertyMapper;

use Rekalogika\Mapper\Attribute\ValueObject;

/**
 * @todo should not be detected as valueobject
 */
#[ValueObject(false)]
class ObjectWithChild1
{
    public readonly ChildObject $child;

    public function __construct()
    {
        $this->child = new ChildObject();
    }
}
