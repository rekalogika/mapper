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

namespace Rekalogika\Mapper\Tests\Fixtures\LazyObject;

class ObjectWithId
{
    public function getId(): string
    {
        return 'id';
    }

    public function getName(): string
    {
        throw new \LogicException('This method should not be called');
    }
}
