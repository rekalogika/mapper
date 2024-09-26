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

final class RootObjectWithoutSetterDto
{
    public ?string $id = null;

    private readonly InnerObjectWithoutSetterDto $innerObject;

    public function __construct()
    {
        $this->innerObject = new InnerObjectWithoutSetterDto();
    }

    public function getInnerObject(): InnerObjectWithoutSetterDto
    {
        return $this->innerObject;
    }
}
