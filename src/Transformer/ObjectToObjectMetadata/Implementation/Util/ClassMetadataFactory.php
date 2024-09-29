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

use Attribute;
use Rekalogika\Mapper\Attribute\ValueObject;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Model\ClassMetadata;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

/**
 * @internal
 */
final readonly class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    public function __construct(
        private EagerPropertiesResolverInterface $eagerPropertiesResolver,
        private PropertyListExtractorInterface $propertyListExtractor,
        private PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
        private DynamicPropertiesDeterminer $dynamicPropertiesDeterminer,
        private AttributesExtractor $attributesExtractor,
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

        $valueObject = $this->isValueObject(
            class: $class,
            attributes: $attributes->toArray(),
            hasWritableDynamicProperties: $hasWritableDynamicProperties,
        );

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
     * @param class-string $class
     * @param list<object> $attributes
     */
    private function isValueObject(
        string $class,
        array $attributes,
        bool $hasWritableDynamicProperties,
    ): bool {
        // if dynamic, then it is not a value object

        if ($hasWritableDynamicProperties) {
            return false;
        }

        // common value object classes

        if (
            is_a($class, \DateTimeImmutable::class, true)
            || is_a($class, \DateInterval::class, true)
            || is_a($class, \DateTimeZone::class, true)
            || is_a($class, \DatePeriod::class, true)
            || is_a($class, \UnitEnum::class, true)
        ) {
            return true;
        }

        // if tagged by ValueObject attribute, then it is a value object

        foreach ($attributes as $attribute) {
            if ($attribute instanceof ValueObject) {
                return $attribute->isValueObject;
            }
        }

        // if all properties are not writable, then it is a value object

        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        foreach ($properties as $property) {
            $writeInfo = $this->propertyWriteInfoExtractor
                ->getWriteInfo($class, $property, [
                    'enable_getter_setter_extraction' => true,
                    'enable_constructor_extraction' => false,
                    'enable_magic_methods_extraction' => true,
                    'enable_adder_remover_extraction' => true,
                ]);

            if (
                $writeInfo !== null
                && $writeInfo->getType() !== PropertyWriteInfo::TYPE_NONE
            ) {
                return false;
            }
        }

        return true;
    }
}
