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

namespace Rekalogika\Mapper\Cache;

class MappingCollection
{
    /**
     * @var list<array{class-string,class-string}>
     */
    private array $classMappings = [];

    /**
     * @param class-string $source
     * @param class-string $target
     */
    public function addObjectMapping(string $source, string $target): self
    {
        $this->classMappings[] = [$source, $target];

        return $this;
    }

    /**
     * @return iterable<array{class-string,class-string}>
     */
    public function getClassMappings(): iterable
    {
        yield from $this->classMappings;
    }
}