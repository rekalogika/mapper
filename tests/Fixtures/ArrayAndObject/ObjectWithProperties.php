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

use Symfony\Component\Serializer\Attribute\Groups;

class ObjectWithProperties
{
    #[Groups(['groupa'])]
    public ?int $a = null;

    #[Groups(['groupb'])]
    public ?string $b = null;

    #[Groups(['groupc'])]
    public ?bool $c = null;

    #[Groups(['groupd'])]
    public ?float $d = null;

    public static function init(): self
    {
        $self = new self();
        $self->a = 1;
        $self->b = 'string';
        $self->c = true;
        $self->d = 1.1;

        return $self;
    }
}
