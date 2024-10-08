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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\ClassMetadataFactory;

use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\ClassMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\DynamicPropertiesDeterminerInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\Model\ClassMetadata;
use Rekalogika\Mapper\Transformer\MetadataUtil\UnalterableDeterminerInterface;
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final readonly class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    public function __construct(
        private EagerPropertiesResolverInterface $eagerPropertiesResolver,
        private DynamicPropertiesDeterminerInterface $dynamicPropertiesDeterminer,
        private AttributesExtractorInterface $attributesExtractor,
        private UnalterableDeterminerInterface $unalterableDeterminer,
    ) {}

    /**
     * @param class-string $class
     */
    #[\Override]
    public function createClassMetadata(string $class): ClassMetadata
    {
        $reflection = new \ReflectionClass($class);

        $hasReadableDynamicProperties =
            $this->dynamicPropertiesDeterminer->allowsDynamicProperties($class)
            || method_exists($class, '__get');

        $hasWritableDynamicProperties =
            $this->dynamicPropertiesDeterminer->allowsDynamicProperties($class)
            || method_exists($class, '__set');

        $internal = $reflection->isInternal();

        $attributes = $this->attributesExtractor->getClassAttributes($class);

        $instantiable = $reflection->isInstantiable();
        $cloneable = $reflection->isCloneable();
        $readOnly = $reflection->isReadOnly();

        $unalterable = $this->unalterableDeterminer->isClassUnalterable($class);

        $eagerProperties = $this->eagerPropertiesResolver
            ->getEagerProperties($class);

        $lastModified = ClassUtil::getLastModifiedTime($class);

        return new ClassMetadata(
            internal: $internal,
            instantiable: $instantiable,
            cloneable: $cloneable,
            readonly: $readOnly,
            unalterable: $unalterable,
            readableDynamicProperties: $hasReadableDynamicProperties,
            writableDynamicProperties: $hasWritableDynamicProperties,
            attributes: $attributes,
            eagerProperties: $eagerProperties,
            lastModified: $lastModified,
        );
    }
}
