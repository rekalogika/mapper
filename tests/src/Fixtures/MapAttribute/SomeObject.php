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

namespace Rekalogika\Mapper\Tests\Fixtures\MapAttribute;

class SomeObject
{
    public ?string $sourcePropertyA = null;

    public ?string $sourcePropertyB = null;

    public ?string $sourcePropertyC = null;

    public static function preinitialized(): self
    {
        $object = new self();
        $object->sourcePropertyA = 'sourcePropertyA';
        $object->sourcePropertyB = 'sourcePropertyB';
        $object->sourcePropertyC = 'sourcePropertyC';

        return $object;
    }
}
