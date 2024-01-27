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

namespace Rekalogika\Mapper\Tests\Fixtures\ArrayAndObject;

class ContainingObject
{
    private ?ObjectWithProperties $data = null;

    public static function create(): self
    {
        $self = new self();
        $self->data = ObjectWithProperties::init();

        return $self;
    }

    public function getData(): ?ObjectWithProperties
    {
        return $this->data;
    }

    public function setData(?ObjectWithProperties $data): self
    {
        $this->data = $data;

        return $this;
    }
}
