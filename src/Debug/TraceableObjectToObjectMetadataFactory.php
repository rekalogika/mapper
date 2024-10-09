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

namespace Rekalogika\Mapper\Debug;

use Rekalogika\Mapper\CacheWarmer\WarmableObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;

/**
 * @internal
 */
final readonly class TraceableObjectToObjectMetadataFactory implements
    ObjectToObjectMetadataFactoryInterface,
    WarmableObjectToObjectMetadataFactoryInterface
{
    public function __construct(
        private ObjectToObjectMetadataFactoryInterface $decorated,
        private MapperDataCollector $dataCollector,
    ) {}

    #[\Override]
    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        $metadata = $this->decorated->createObjectToObjectMetadata($sourceClass, $targetClass);

        $this->dataCollector->collectObjectToObjectMetadata($metadata);

        return $metadata;
    }

    #[\Override]
    public function warmingCreateObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        if ($this->decorated instanceof WarmableObjectToObjectMetadataFactoryInterface) {
            return $this->decorated
                ->warmingCreateObjectToObjectMetadata($sourceClass, $targetClass);
        }

        return $this->createObjectToObjectMetadata($sourceClass, $targetClass);
    }
}
