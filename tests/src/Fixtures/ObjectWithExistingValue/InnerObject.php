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

namespace Rekalogika\Mapper\Tests\Fixtures\ObjectWithExistingValue;

final class InnerObject
{
    private ?string $property = null;

    private FurtherInnerObject $furtherInnerObject;

    public function __construct()
    {
        $this->furtherInnerObject = new FurtherInnerObject();
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    public function setProperty(?string $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getFurtherInnerObject(): FurtherInnerObject
    {
        return $this->furtherInnerObject;
    }

    public function setFurtherInnerObject(FurtherInnerObject $furtherInnerObject): self
    {
        $this->furtherInnerObject = $furtherInnerObject;

        return $this;
    }
}
