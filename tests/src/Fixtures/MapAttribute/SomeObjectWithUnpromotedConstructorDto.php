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

class SomeObjectWithUnpromotedConstructorDto
{
    public ?string $targetPropertyA = null;
    public ?string $targetPropertyB = null;
    public ?string $targetPropertyC = null;

    public function __construct(
        #[Map(property: 'sourcePropertyA')]
        #[Map(property: 'targetPropertyA', class: OtherObject::class)]
        ?string $targetPropertyA = null,
        ?string $targetPropertyB = null,
        ?string $targetPropertyC = null,
    ) {
        $this->targetPropertyA = $targetPropertyA;
        $this->targetPropertyB = $targetPropertyB;
        $this->targetPropertyC = $targetPropertyC;
    }

    public static function preinitialized(): self
    {
        return new self(
            'targetPropertyA',
            'targetPropertyB',
            'targetPropertyC',
        );
    }
}
