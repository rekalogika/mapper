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

use Rekalogika\Mapper\Attribute\Map;

class SomeObjectSkippingMapping
{
    #[Map(property: null)]
    public ?string $sourcePropertyA = null;

    #[Map(property: null)]
    public ?string $sourcePropertyB = null;

    #[Map(property: null)]
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
