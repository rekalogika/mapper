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

class OtherObject
{
    public ?string $otherSourcePropertyA = null;

    public ?string $otherSourcePropertyB = null;

    public ?string $otherSourcePropertyC = null;

    public static function preinitialized(): self
    {
        $object = new self();
        $object->otherSourcePropertyA = 'otherSourcePropertyA';
        $object->otherSourcePropertyB = 'otherSourcePropertyB';
        $object->otherSourcePropertyC = 'otherSourcePropertyC';

        return $object;
    }
}
