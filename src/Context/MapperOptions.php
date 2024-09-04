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

namespace Rekalogika\Mapper\Context;

/**
 * @immutable
 */
final readonly class MapperOptions
{
    /**
     * @param boolean $lazyLoading Enable lazy loading.
     * @param boolean $readTargetValue If disabled, values on the target side will not be read, and assumed to be null.
     * @param boolean $objectToObjectScalarShortCircuit Performance optimization by doing scalar to scalar transformation within `ObjectToObjectTransformer` instead of delegating to the `MainTransformer`
     */
    public function __construct(
        public bool $lazyLoading = true,
        public bool $readTargetValue = true,
        public bool $objectToObjectScalarShortCircuit = true,
    ) {}
}
