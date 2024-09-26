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

namespace Rekalogika\Mapper\Tests\Fixtures\ObjectWithExistingValueDto;

final class InnerObjectWithoutSetterDto
{
    public ?string $property = null;

    private readonly FurtherInnerObjectDto $furtherInnerObject;

    public function __construct()
    {
        $this->furtherInnerObject = new FurtherInnerObjectDto();
    }

    public function getFurtherInnerObject(): FurtherInnerObjectDto
    {
        return $this->furtherInnerObject;
    }
}
