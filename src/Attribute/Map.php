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

/**
 * Defines the property to be mapped from or to.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final readonly class Map
{
    public ?string $property;

    /**
     * @param class-string|null $class
     */
    public function __construct(
        null|string|false $property = null,
        public ?string $class = null,
    ) {
        if ($property === false) {
            $this->property = null;
        } else {
            $this->property = $property;
        }
    }
}
