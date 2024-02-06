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

use Rekalogika\Mapper\Attribute\InheritanceMap;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\Exception\InternalClassUnsupportedException;
use Rekalogika\Mapper\Transformer\Exception\SourceClassNotInInheritanceMapException;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

final class ObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    public function __construct(
        private PropertyListExtractorInterface $propertyListExtractor,
        private PropertyInitializableExtractorInterface $propertyInitializableExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyMapperResolverInterface $propertyMapperResolver,
        private PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        private PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
    ) {
    }

    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
        Context $context
    ): ObjectToObjectMetadata {
        $providedTargetClass = $targetClass;

        $sourceReflection = new \ReflectionClass($sourceClass);
        $providedTargetReflection = new \ReflectionClass($providedTargetClass);

        // check inheritance map

        $attributes = $providedTargetReflection->getAttributes(InheritanceMap::class);

        if (count($attributes) > 0) {
            $inheritanceMap = $attributes[0]->newInstance();

            $targetClass = $inheritanceMap->getTargetClassFromSourceClass($sourceClass);

            if ($targetClass === null) {
                throw new SourceClassNotInInheritanceMapException($sourceClass, $providedTargetClass, context: $context);
            }
        }

        $targetReflection = new \ReflectionClass($targetClass);

        // check if source and target classes are internal

        if ($sourceReflection->isInternal()) {
            throw new InternalClassUnsupportedException($sourceClass, context: $context);
        }

        if ($targetReflection->isInternal()) {
            throw new InternalClassUnsupportedException($targetClass, context: $context);
        }

        // queries

        $initializableTargetProperties = $this
            ->listInitializableProperties($targetClass, $context);
        $targetProperties = $this
            ->listProperties($targetClass, $context);

        $initializableTargetPropertiesNotInSource = $initializableTargetProperties;

        // determine if targetClass is instantiable

        $instantiable = $targetReflection->isInstantiable();
        $cloneable = $targetReflection->isCloneable();

        // determine last modified

        $sourceModifiedTime = ClassUtil::getLastModifiedTime($sourceReflection);
        $targetModifiedTime = ClassUtil::getLastModifiedTime($targetReflection);

        // process properties mapping

        $propertyMappings = [];

        foreach ($targetProperties as $targetProperty) {
            $sourceProperty = $targetProperty;

            // determine if a property mapper is defined for the property

            $serviceMethodSpecification = $this->propertyMapperResolver
                ->getPropertyMapper($sourceClass, $targetClass, $targetProperty);

            // get read & write info for source and target properties

            $sourceReadInfo = $this->propertyReadInfoExtractor
                ->getReadInfo($sourceClass, $sourceProperty);
            $targetReadInfo = $this->propertyReadInfoExtractor
                ->getReadInfo($targetClass, $targetProperty);
            $targetWriteInfo = $this->propertyWriteInfoExtractor
                ->getWriteInfo($targetClass, $targetProperty);

            // process source read mode

            if ($sourceReadInfo === null) {
                $sourceReadMode = ReadMode::None;
                $sourceReadName = null;
                $sourceReadVisibility = Visibility::None;
            } else {
                $sourceReadMode = match ($sourceReadInfo->getType()) {
                    PropertyReadInfo::TYPE_METHOD => ReadMode::Method,
                    PropertyReadInfo::TYPE_PROPERTY => ReadMode::Property,
                    default => ReadMode::None,
                };

                $sourceReadName = $sourceReadInfo->getName();

                $sourceReadVisibility = match ($sourceReadInfo->getVisibility()) {
                    PropertyReadInfo::VISIBILITY_PUBLIC => Visibility::Public,
                    PropertyReadInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                    PropertyReadInfo::VISIBILITY_PRIVATE => Visibility::Private,
                    default => Visibility::None,
                };
            }

            // process target read mode

            if ($targetReadInfo === null) {
                $targetReadMode = ReadMode::None;
                $targetReadName = null;
                $targetReadVisibility = Visibility::None;
            } else {
                $targetReadMode = match ($targetReadInfo->getType()) {
                    PropertyReadInfo::TYPE_METHOD => ReadMode::Method,
                    PropertyReadInfo::TYPE_PROPERTY => ReadMode::Property,
                    default => ReadMode::None,
                };

                $targetReadName = $targetReadInfo->getName();

                $targetReadVisibility = match ($targetReadInfo->getVisibility()) {
                    PropertyReadInfo::VISIBILITY_PUBLIC => Visibility::Public,
                    PropertyReadInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                    PropertyReadInfo::VISIBILITY_PRIVATE => Visibility::Private,
                    default => Visibility::None,
                };
            }

            // process target write mode

            if (
                $targetWriteInfo === null
            ) {
                $targetWriteMode = WriteMode::None;
                $targetWriteName = null;
                $targetWriteVisibility = Visibility::None;
            } elseif ($targetWriteInfo->getType() === PropertyWriteInfo::TYPE_ADDER_AND_REMOVER) {
                $targetWriteMode = WriteMode::AdderRemover;
                $targetWriteName = $targetWriteInfo->getAdderInfo()->getName();
                $targetWriteVisibility = match ($targetWriteInfo->getAdderInfo()->getVisibility()) {
                    PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                    PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                    PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                    default => Visibility::None,
                };
            } elseif ($targetWriteInfo->getType() === PropertyWriteInfo::TYPE_CONSTRUCTOR) {
                $targetWriteMode = WriteMode::Constructor;
                $targetWriteName = $targetWriteInfo->getName();
                $targetWriteVisibility = Visibility::None;
            } else {
                $targetWriteMode = match ($targetWriteInfo->getType()) {
                    PropertyWriteInfo::TYPE_METHOD => WriteMode::Method,
                    PropertyWriteInfo::TYPE_PROPERTY => WriteMode::Property,
                    default => WriteMode::None,
                };

                if ($targetWriteMode === WriteMode::None) {
                    $targetWriteName = null;
                    $targetWriteVisibility = Visibility::None;
                } else {
                    $targetWriteName = $targetWriteInfo->getName();
                    $targetWriteVisibility = match ($targetWriteInfo->getVisibility()) {
                        PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                        PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                        PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                        default => Visibility::None,
                    };
                }
            }

            // get source property types

            $sourcePropertyTypes = $this->propertyTypeExtractor
                ->getTypes($sourceClass, $sourceProperty) ?? [];

            // get target property types

            $targetPropertyTypes = $this->propertyTypeExtractor
                ->getTypes($targetClass, $targetProperty);

            /** @var 'int'|'float'|'string'|'bool'|null */
            $targetPropertyScalarType = null;

            if (null === $targetPropertyTypes || count($targetPropertyTypes) === 0) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot get type of target property "%s::$%s".',
                        $targetClass,
                        $targetProperty
                    ),
                    context: $context
                );
            } elseif (count($targetPropertyTypes) === 1) {
                $targetPropertyType = $targetPropertyTypes[0];
                $targetPropertyBuiltInType = $targetPropertyType->getBuiltinType();

                if (in_array($targetPropertyBuiltInType, ['int', 'float', 'string', 'bool'], true)) {
                    $targetPropertyScalarType = $targetPropertyBuiltInType;
                }
            }

            $propertyMapping = new PropertyMapping(
                sourceProperty: $sourceReadMode !== ReadMode::None ? $sourceProperty : null,
                targetProperty: $targetProperty,
                sourceTypes: $sourcePropertyTypes,
                targetTypes: $targetPropertyTypes,
                sourceReadMode: $sourceReadMode,
                sourceReadName: $sourceReadName,
                sourceReadVisibility: $sourceReadVisibility,
                targetReadMode: $targetReadMode,
                targetReadName: $targetReadName,
                targetReadVisibility: $targetReadVisibility,
                targetWriteMode: $targetWriteMode,
                targetWriteName: $targetWriteName,
                targetWriteVisibility: $targetWriteVisibility,
                targetScalarType: $targetPropertyScalarType,
                propertyMapper: $serviceMethodSpecification,
            );

            $propertyMappings[] = $propertyMapping;
        }

        $objectToObjectMetadata = new ObjectToObjectMetadata(
            sourceClass: $sourceClass,
            targetClass: $targetClass,
            providedTargetClass: $providedTargetClass,
            propertyMappings: $propertyMappings,
            instantiable: $instantiable,
            cloneable: $cloneable,
            initializableTargetPropertiesNotInSource: $initializableTargetPropertiesNotInSource,
            sourceModifiedTime: $sourceModifiedTime,
            targetModifiedTime: $targetModifiedTime,
        );

        return $objectToObjectMetadata;
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     */
    private function listProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        return array_values($properties);
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     */
    private function listInitializableProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->listProperties($class, $context);

        $initializableProperties = [];

        foreach ($properties as $property) {
            if ($this->propertyInitializableExtractor->isInitializable($class, $property)) {
                $initializableProperties[] = $property;
            }
        }

        return $initializableProperties;
    }
}
