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

use Rekalogika\Mapper\Attribute\Map;

class SomeObjectDto
{
    #[Map(from: 'sourcePropertyA')]
    public ?string $targetPropertyA = null;
    private ?string $targetPropertyB = null;
    private ?string $targetPropertyC = null;

    #[Map(from: 'sourcePropertyB')]
    public function setTargetPropertyB(string $value): void
    {
        $this->targetPropertyB = $value;
    }

    public function getTargetPropertyB(): ?string
    {
        return $this->targetPropertyB;
    }

    public function setTargetPropertyC(string $value): void
    {
        $this->targetPropertyC = $value;
    }

    #[Map(from: 'sourcePropertyC')]
    public function getTargetPropertyC(): ?string
    {
        return $this->targetPropertyC;
    }
}
