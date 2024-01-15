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

namespace Rekalogika\Mapper\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class InheritanceMap implements MapperAttributeInterface
{
    /**
     * @param array<class-string,class-string> $map
     */
    public function __construct(
        private array $map = []
    ) {
    }

    /**
     * @return array<class-string,class-string>
     */
    public function getMap(): array
    {
        return $this->map;
    }
}
