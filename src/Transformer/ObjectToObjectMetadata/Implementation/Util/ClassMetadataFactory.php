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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util;

use Rekalogika\Mapper\Attribute\ValueObject;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Model\ClassMetadata;
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final readonly class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    public function __construct(
        private EagerPropertiesResolverInterface $eagerPropertiesResolver,
    ) {}

    /**
     * @param class-string $class
     */
    #[\Override]
    public function createClassMetadata(string $class): ClassMetadata
    {
        $reflection = new \ReflectionClass($class);

        $hasReadableDynamicProperties =
            $this->allowsDynamicProperties($reflection)
            || method_exists($class, '__get');

        $hasWritableDynamicProperties =
            $this->allowsDynamicProperties($reflection)
            || method_exists($class, '__set');

        $internal = $reflection->isInternal();

        $attributes = ClassUtil::getClassAttributes($class, null);

        $instantiable = $reflection->isInstantiable();
        $cloneable = $reflection->isCloneable();
        $readOnly = $reflection->isReadOnly();
        $valueObject = $this->isValueObject($attributes);

        $eagerProperties = $this->eagerPropertiesResolver
            ->getEagerProperties($class);

        $lastModified = ClassUtil::getLastModifiedTime($class);

        return new ClassMetadata(
            internal: $internal,
            instantiable: $instantiable,
            cloneable: $cloneable,
            readonly: $readOnly,
            valueObject: $valueObject,
            readableDynamicProperties: $hasReadableDynamicProperties,
            writableDynamicProperties: $hasWritableDynamicProperties,
            attributes: $attributes,
            eagerProperties: $eagerProperties,
            lastModified: $lastModified,
        );
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private function allowsDynamicProperties(\ReflectionClass $class): bool
    {
        do {
            if ($class->getAttributes(\AllowDynamicProperties::class) !== []) {
                return true;
            }
        } while ($class = $class->getParentClass());

        return false;
    }

    /**
     * @param list<object> $attributes
     */
    private function isValueObject(
        array $attributes,
    ): bool {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof ValueObject) {
                return true;
            }
        }

        return false;
    }
}
