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

namespace Rekalogika\Mapper\Tests\Fixtures\Constructor;

use Rekalogika\Mapper\Attribute\Eager;

#[Eager]
final readonly class ObjectWithMandatoryConstructorThatCannotBeCastFromNullDto
{
    public function __construct(
        private \DateTimeInterface $a,
    ) {}

    public function getA(): \DateTimeInterface
    {
        return $this->a;
    }
}
