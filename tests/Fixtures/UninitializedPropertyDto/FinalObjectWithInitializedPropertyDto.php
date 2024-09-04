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

namespace Rekalogika\Mapper\Tests\Fixtures\UninitializedPropertyDto;

/**
 * Final object won't be lazy loaded.
 */
class FinalObjectWithInitializedPropertyDto
{
    public ValueObjectDto $property;

    public function __construct()
    {
        $this->property = new ValueObjectDto('bar');
    }
}
