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

namespace Rekalogika\Mapper\Tests\Fixtures\WitherMethod;

class ObjectWithImmutableSetter
{
    public function __construct(
        private ?string $property = null,
    ) {}

    public function setProperty(?string $property): self
    {
        return new self($property);
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }
}
