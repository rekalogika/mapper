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

use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;

/**
 * @internal
 */
final readonly class ProxyResolvingObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    public function __construct(
        private ObjectToObjectMetadataFactoryInterface $decorated,
    ) {
    }

    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        $metadata = $this->decorated->createObjectToObjectMetadata(
            $this->resolveRealClass($sourceClass),
            $targetClass,
        );

        return $metadata;
    }

    /**
     * @param class-string $class
     * @return class-string
     */
    private function resolveRealClass(string $class): string
    {
        $pos = strrpos($class, '\\__CG__\\');

        if ($pos === false) {
            $pos = strrpos($class, '\\__PM__\\');
        }

        if ($pos !== false) {
            $class = substr($class, $pos + 8);
        }

        assert(class_exists($class), sprintf('Class "%s" does not exist', $class));

        return $class;
    }
}
