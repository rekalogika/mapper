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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation;

use Rekalogika\Mapper\CacheWarmer\WarmableObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final readonly class ProxyResolvingObjectToObjectMetadataFactory implements
    ObjectToObjectMetadataFactoryInterface,
    WarmableObjectToObjectMetadataFactoryInterface
{
    public function __construct(
        private ObjectToObjectMetadataFactoryInterface $decorated,
    ) {}

    #[\Override]
    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        return $this->decorated->createObjectToObjectMetadata(
            ClassUtil::determineRealClassFromPossibleProxy($sourceClass),
            $targetClass,
        );
    }

    #[\Override]
    public function warmingCreateObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        if ($this->decorated instanceof WarmableObjectToObjectMetadataFactoryInterface) {
            return $this->decorated->warmingCreateObjectToObjectMetadata(
                ClassUtil::determineRealClassFromPossibleProxy($sourceClass),
                $targetClass,
            );
        }

        return $this->createObjectToObjectMetadata($sourceClass, $targetClass);
    }
}
