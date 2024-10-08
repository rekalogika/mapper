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

namespace Rekalogika\Mapper\CacheWarmer;

use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;

interface WarmableObjectToObjectMetadataFactoryInterface
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function warmingCreateObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata;
}
