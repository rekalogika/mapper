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

namespace Rekalogika\Mapper\Tests\Fixtures\Doctrine;

class EntityWithSingleIdentifierDto
{
    public ?string $myIdentifier = null;
    public ?string $name = null;
    public ?self $parent = null;

    /**
     * @var array<int,self>
     */
    public array $children = [];
}
