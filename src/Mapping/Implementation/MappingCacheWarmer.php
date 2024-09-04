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

namespace Rekalogika\Mapper\Mapping\Implementation;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * @internal
 */
final readonly class MappingCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private WarmableMappingFactory $warmableMappingFactory,
    ) {
    }

    #[\Override]
    public function isOptional(): bool
    {
        return false;
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->warmableMappingFactory->warmUp();

        return [];
    }
}
