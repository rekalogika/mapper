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

namespace Rekalogika\Mapper\Tests\Fixtures\WitherMethod;

use Rekalogika\Mapper\Attribute\ValueObject;

#[ValueObject(false)]
class ParentObjectWithObjectWithVoidSetter
{
    private ObjectWithVoidSetter $object;

    public function __construct()
    {
        $this->object = new ObjectWithVoidSetter();
    }

    public function getObject(): ObjectWithVoidSetter
    {
        return $this->object;
    }
}
