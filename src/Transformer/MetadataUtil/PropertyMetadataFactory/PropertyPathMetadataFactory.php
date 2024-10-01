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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\PropertyMetadataFactory;

use Rekalogika\Mapper\Transformer\Exception\PropertyPathAwarePropertyInfoExtractorException;
use Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\Model\Attributes;
use Rekalogika\Mapper\Transformer\MetadataUtil\Model\PropertyMetadata;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyAccessInfoExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\UnalterableDeterminer;
use Rekalogika\Mapper\Transformer\MetadataUtil\Util;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class PropertyPathMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyAccessInfoExtractorInterface $propertyAccessInfoExtractor,
        private AttributesExtractorInterface $attributesExtractor,
        private UnalterableDeterminer $unalterableDeterminer,
    ) {}

    #[\Override]
    public function createPropertyMetadata(
        string $class,
        string $property,
    ): PropertyMetadata {
        $propertyPathObject = new PropertyPath($property);

        /** @var \Iterator&PropertyPathIteratorInterface */
        $iterator = $propertyPathObject->getIterator();

        $currentPath = '';

        $currentClass = $class;

        $currentType = null;

        $currentProperty = null;

        $lastClass = null;

        $lastIsIndex = false;

        /** @var list<Type>|null */
        $types = null;

        foreach ($iterator as $propertyPathPart) {
            \assert(\is_string($propertyPathPart));

            if ($types !== null) {
                if (\count($types) > 1) {
                    throw new PropertyPathAwarePropertyInfoExtractorException(
                        message: \sprintf('Cannot proceed because property "%s" has multiple types in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                        class: $class,
                        propertyPath: $property,
                    );
                }

                $currentType = $types[0];
                $currentClass = $currentType->getClassName();
            }

            if ($iterator->isIndex()) {
                $lastIsIndex = true;
                $currentPath .= '[' . $propertyPathPart . ']';
                $types = $currentType?->getCollectionValueTypes();
            } else {
                $lastIsIndex = false;
                if ($currentClass === null) {
                    throw new PropertyPathAwarePropertyInfoExtractorException(
                        message: \sprintf('Trying to resolve path "%s", but the current node is not an object', $propertyPathPart),
                        class: $class,
                        propertyPath: $property,
                    );
                }

                $currentPath .= '.' . $propertyPathPart;
                $currentProperty = $propertyPathPart;
                $lastClass = $currentClass;
                $types = $this->propertyTypeExtractor
                    ->getTypes($currentClass, $propertyPathPart);
            }

            if ($types === null) {
                throw new PropertyPathAwarePropertyInfoExtractorException(
                    message: \sprintf('Property "%s" not found in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                    class: $class,
                    propertyPath: $property,
                );
            } elseif (\count($types) === 0) {
                throw new PropertyPathAwarePropertyInfoExtractorException(
                    message: \sprintf('Cannot determine the type of property "%s" in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                    class: $class,
                    propertyPath: $property,
                );
            }
        }

        if ($lastClass === null) {
            throw new PropertyPathAwarePropertyInfoExtractorException(
                message: \sprintf('Property path "%s" is empty', $property),
                class: $class,
                propertyPath: $property,
            );
        }

        if (!class_exists($lastClass)) {
            throw new PropertyPathAwarePropertyInfoExtractorException(
                message: \sprintf('Class "%s" not found', $lastClass),
                class: $class,
                propertyPath: $property,
            );
        }

        if ($currentProperty !== null) {
            $attributes = $this->attributesExtractor
                ->getPropertyAttributes($lastClass, $currentProperty);
        } else {
            $attributes = [];
        }

        $attributes = new Attributes($attributes);

        $replaceable = $this->isReplaceable(
            class: $lastClass,
            property: $currentProperty,
            isIndex: $lastIsIndex,
        );

        $types = array_values($types ?? []);

        $unalterable = $this->unalterableDeterminer
            ->isTypesUnalterable($types);

        $mutableByHost = $this->isMutableByHost(
            class: $lastClass,
            property: $currentProperty,
            isIndex: $lastIsIndex,
        );

        return new PropertyMetadata(
            readMode: ReadMode::PropertyPath,
            readName: $property,
            readVisibility: Visibility::Public,
            constructorWriteMode: WriteMode::None,
            constructorWriteName: null,
            constructorMandatory: false,
            constructorVariadic: false,
            setterWriteMode: WriteMode::PropertyPath,
            setterWriteName: $property,
            setterWriteVisibility: Visibility::Public,
            setterVariadic: false,
            removerWriteName: $property,
            removerWriteVisibility: Visibility::Public,
            types: $types,
            scalarType: Util::determineScalarType($types),
            nullable: false,
            replaceable: $replaceable,
            unalterable: $unalterable,
            mutableByHost: $mutableByHost,
            attributes: $attributes,
        );
    }

    /**
     * @param class-string $class
     */
    private function isReplaceable(
        string $class,
        ?string $property,
        bool $isIndex,
    ): bool {
        if ($property === null) {
            return $isIndex;
        }

        $writeInfo = $this->propertyAccessInfoExtractor
            ->getWriteInfo($class, $property);

        if ($writeInfo === null) {
            return false;
        }

        return
            \in_array(
                $writeInfo->getType(),
                [PropertyWriteInfo::TYPE_METHOD, PropertyWriteInfo::TYPE_PROPERTY],
                true,
            )
            && $writeInfo->getVisibility() === PropertyWriteInfo::VISIBILITY_PUBLIC;
    }

    /**
     * @param class-string $class
     */
    private function isMutableByHost(
        string $class,
        ?string $property,
        bool $isIndex,
    ): bool {
        if ($property === null) {
            return $isIndex;
        }

        $writeInfo = $this->propertyAccessInfoExtractor
            ->getWriteInfo($class, $property);

        if ($writeInfo === null) {
            return false;
        }

        return
            $writeInfo->getType() === PropertyWriteInfo::TYPE_ADDER_AND_REMOVER;
    }
}
