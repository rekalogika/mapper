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

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;

final class TraceableObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    public function __construct(
        private ObjectToObjectMetadataFactoryInterface $decorated,
        private MapperDataCollector $dataCollector
    ) {
    }

    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
        Context $context
    ): ObjectToObjectMetadata {
        $metadata = $this->decorated->createObjectToObjectMetadata($sourceClass, $targetClass, $context);

        $this->dataCollector->collectObjectToObjectMetadata($metadata);

        return $metadata;
    }
}
