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

final class RootObject
{
    private string $id = '_';
    private InnerObject $innerObject;

    public function __construct()
    {
        $this->innerObject = new InnerObject();
    }

    public function getInnerObject(): InnerObject
    {
        return $this->innerObject;
    }

    public function setInnerObject(InnerObject $innerObject): self
    {
        $this->innerObject = $innerObject;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }
}
