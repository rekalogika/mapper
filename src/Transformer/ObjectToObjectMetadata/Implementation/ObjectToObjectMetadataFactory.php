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

use Rekalogika\Mapper\Attribute\AllowDelete;
use Rekalogika\Mapper\Attribute\InheritanceMap;
use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\Exception\InternalClassUnsupportedException;
use Rekalogika\Mapper\Transformer\Exception\SourceClassNotInInheritanceMapException;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class ObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    public function __construct(
        private PropertyListExtractorInterface $propertyListExtractor,
        private PropertyInitializableExtractorInterface $propertyInitializableExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyMapperResolverInterface $propertyMapperResolver,
        private PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        private PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
        private EagerPropertiesResolverInterface $eagerPropertiesResolver,
        private ProxyFactoryInterface $proxyFactory,
        private TypeResolverInterface $typeResolver,
    ) {}

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     *
     * @return class-string
     */
    private function resolveTargetClass(
        string $sourceClass,
        string $targetClass
    ): string {
        $sourceReflection = new \ReflectionClass($sourceClass);
        $targetReflection = new \ReflectionClass($targetClass);

        $targetAttributes = $targetReflection->getAttributes(InheritanceMap::class);

        if ([] !== $targetAttributes) {
            // if the target has an InheritanceMap, we try to resolve the target
            // class using the InheritanceMap

            $inheritanceMap = $targetAttributes[0]->newInstance();

            $resolvedTargetClass = $inheritanceMap->getTargetClassFromSourceClass($sourceClass);

            if (null === $resolvedTargetClass) {
                throw new SourceClassNotInInheritanceMapException($sourceClass, $targetClass);
            }

            return $resolvedTargetClass;
        }

        if ($targetReflection->isAbstract() || $targetReflection->isInterface()) {
            // if target doesn't have an inheritance map, but is also abstract
            // or an interface, we try to find the InheritanceMap from the
            // source

            $sourceClasses = ClassUtil::getAllClassesFromObject($sourceClass);

            foreach ($sourceClasses as $currentSourceClass) {
                $sourceReflection = new \ReflectionClass($currentSourceClass);
                $sourceAttributes = $sourceReflection->getAttributes(InheritanceMap::class);

                if ([] !== $sourceAttributes) {
                    $inheritanceMap = $sourceAttributes[0]->newInstance();

                    $resolvedTargetClass = $inheritanceMap->getSourceClassFromTargetClass($sourceClass);

                    if (null === $resolvedTargetClass) {
                        throw new SourceClassNotInInheritanceMapException($currentSourceClass, $targetClass);
                    }

                    return $resolvedTargetClass;
                }
            }
        }

        return $targetClass;
    }

    #[\Override]
    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        $providedTargetClass = $targetClass;
        $sourceReflection = new \ReflectionClass($sourceClass);

        $targetClass = $this->resolveTargetClass($sourceClass, $providedTargetClass);
        $targetReflection = new \ReflectionClass($targetClass);

        // dynamic properties

        $sourceAllowsDynamicProperties = $this->allowsDynamicProperties($sourceReflection);
        $targetAllowsDynamicProperties = $this->allowsDynamicProperties($targetReflection);

        // check if source and target classes are internal. we allow stdClass at
        // the source side
        if (!$sourceAllowsDynamicProperties && $sourceReflection->isInternal()) {
            throw new InternalClassUnsupportedException($sourceClass);
        }

        if (!$targetAllowsDynamicProperties && $targetReflection->isInternal()) {
            throw new InternalClassUnsupportedException($targetClass);
        }

        // queries

        $initializableTargetProperties = $this
            ->listInitializableProperties($targetClass);
        $targetProperties = $this
            ->listProperties($targetClass);

        $initializableTargetPropertiesNotInSource = $initializableTargetProperties;

        // determine if targetClass is instantiable

        $instantiable = $targetReflection->isInstantiable();
        $cloneable = $targetReflection->isCloneable();

        // determine the list of eager properties

        $eagerProperties = $this->eagerPropertiesResolver
            ->getEagerProperties($sourceClass);

        // determine if target read only

        $targetReadOnly = $targetReflection->isReadOnly();

        // determine last modified

        $sourceModifiedTime = ClassUtil::getLastModifiedTime($sourceReflection);
        $targetModifiedTime = ClassUtil::getLastModifiedTime($targetReflection);

        // process properties mapping

        $propertyMappings = [];

        // determine properties to map

        if ($targetAllowsDynamicProperties) {
            $sourceProperties = $this->listProperties($sourceClass);
            $propertiesToMap = array_unique(array_merge($sourceProperties, $targetProperties));
        } else {
            $propertiesToMap = $targetProperties;
        }

        // iterate over properties to map

        $effectivePropertiesToMap = [];

        foreach ($propertiesToMap as $targetProperty) {
            $sourceProperty = $targetProperty;

            $serviceMethodSpecification = $this->propertyMapperResolver
                ->getPropertyMapper($sourceClass, $targetClass, $targetProperty);

            // get reflection for target property

            try {
                $targetPropertyReflection = $targetReflection->getProperty($targetProperty);
            } catch (\ReflectionException) {
                $targetPropertyReflection = null;
            }

            // get read & write info for source and target properties

            $sourceReadInfo = $this->propertyReadInfoExtractor
                ->getReadInfo($sourceClass, $sourceProperty);
            $targetReadInfo = $this->propertyReadInfoExtractor
                ->getReadInfo($targetClass, $targetProperty);
            $targetConstructorWriteInfo = $this
                ->getConstructorWriteInfo($targetClass, $targetProperty);
            $targetSetterWriteInfo = $this
                ->getSetterWriteInfo($targetClass, $targetProperty);

            // determine if target allows delete

            if (null === $targetPropertyReflection) {
                $targetAllowsDelete = false;
            } else {
                $targetAllowsDelete = [] !== $targetPropertyReflection->getAttributes(AllowDelete::class);
            }

            // process source read mode

            if (null === $sourceReadInfo) {
                // if source allows dynamic properties, including stdClass
                if ($sourceAllowsDynamicProperties) {
                    $sourceReadMode = ReadMode::DynamicProperty;
                    $sourceReadName = $sourceProperty;
                    $sourceReadVisibility = Visibility::Public;
                } else {
                    $sourceReadMode = ReadMode::None;
                    $sourceReadName = null;
                    $sourceReadVisibility = Visibility::None;
                }
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

            if (null === $targetReadInfo) {
                // if source allows dynamic properties, including stdClass
                if ($targetAllowsDynamicProperties) {
                    $targetReadMode = ReadMode::DynamicProperty;
                    $targetReadName = $targetProperty;
                    $targetReadVisibility = Visibility::Public;
                } else {
                    $targetReadMode = ReadMode::None;
                    $targetReadName = null;
                    $targetReadVisibility = Visibility::None;
                }
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

            // skip if target is not writable

            if (null === $targetConstructorWriteInfo && null === $targetSetterWriteInfo) {
                continue;
            }

            // process target constructor write info

            if (
                null === $targetConstructorWriteInfo
                || PropertyWriteInfo::TYPE_CONSTRUCTOR !== $targetConstructorWriteInfo->getType()
            ) {
                $targetConstructorWriteMode = WriteMode::None;
                $targetConstructorWriteName = null;
            } else {
                $targetConstructorWriteMode = WriteMode::Constructor;
                $targetConstructorWriteName = $targetConstructorWriteInfo->getName();
            }

            // process target setter write mode

            $targetRemoverWriteName = null;
            $targetRemoverWriteVisibility = Visibility::None;

            if (null === $targetSetterWriteInfo) {
                $targetSetterWriteMode = WriteMode::None;
                $targetSetterWriteName = null;
                $targetSetterWriteVisibility = Visibility::None;
            } elseif (PropertyWriteInfo::TYPE_ADDER_AND_REMOVER === $targetSetterWriteInfo->getType()) {
                $targetSetterWriteMode = WriteMode::AdderRemover;
                $targetSetterWriteName = $targetSetterWriteInfo->getAdderInfo()->getName();
                $targetRemoverWriteName = $targetSetterWriteInfo->getRemoverInfo()->getName();
                $targetSetterWriteVisibility = match ($targetSetterWriteInfo->getAdderInfo()->getVisibility()) {
                    PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                    PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                    PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                    default => Visibility::None,
                };
                $targetRemoverWriteVisibility = match ($targetSetterWriteInfo->getRemoverInfo()->getVisibility()) {
                    PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                    PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                    PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                    default => Visibility::None,
                };
            } else {
                $targetSetterWriteMode = match ($targetSetterWriteInfo->getType()) {
                    PropertyWriteInfo::TYPE_METHOD => WriteMode::Method,
                    PropertyWriteInfo::TYPE_PROPERTY => WriteMode::Property,
                    default => WriteMode::None,
                };

                if (WriteMode::None === $targetSetterWriteMode) {
                    if ($targetAllowsDynamicProperties && null === $targetReadInfo) {
                        $targetSetterWriteMode = WriteMode::DynamicProperty;
                        $targetSetterWriteName = $targetProperty;
                        $targetSetterWriteVisibility = Visibility::Public;
                    } else {
                        $targetSetterWriteName = null;
                        $targetSetterWriteVisibility = Visibility::None;
                    }
                } else {
                    $targetSetterWriteName = $targetSetterWriteInfo->getName();
                    $targetSetterWriteVisibility = match ($targetSetterWriteInfo->getVisibility()) {
                        PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                        PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                        PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                        default => Visibility::None,
                    };
                }
            }

            // get source property types

            $originalSourcePropertyTypes = $this->propertyTypeExtractor
                ->getTypes($sourceClass, $sourceProperty) ?? [];
            $sourcePropertyTypes = [];

            foreach ($originalSourcePropertyTypes as $sourcePropertyType) {
                $simpleTypes = $this->typeResolver->getSimpleTypes($sourcePropertyType);

                foreach ($simpleTypes as $simpleType) {
                    if ($simpleType instanceof MixedType) {
                        continue;
                    }

                    $sourcePropertyTypes[] = $simpleType;
                }
            }

            // get target property types

            $originalTargetPropertyTypes = $this->propertyTypeExtractor
                ->getTypes($targetClass, $targetProperty) ?? [];
            $targetPropertyTypes = [];

            foreach ($originalTargetPropertyTypes as $targetPropertyType) {
                $simpleTypes = $this->typeResolver->getSimpleTypes($targetPropertyType);

                foreach ($simpleTypes as $simpleType) {
                    if ($simpleType instanceof MixedType) {
                        continue;
                    }

                    $targetPropertyTypes[] = $simpleType;
                }
            }

            // determine target scalar type

            /** @var null|'bool'|'float'|'int'|'null'|'string' */
            $targetPropertyScalarType = null;

            if (1 === count($originalTargetPropertyTypes)) {
                $targetPropertyType = $originalTargetPropertyTypes[0];
                $targetPropertyBuiltInType = $targetPropertyType->getBuiltinType();

                if (in_array(
                    $targetPropertyBuiltInType,
                    ['int', 'float', 'string', 'bool', 'null'],
                    true
                )) {
                    $targetPropertyScalarType = $targetPropertyBuiltInType;
                }
            }

            // determine if target can accept null

            $targetCanAcceptNull = false;

            foreach ($targetPropertyTypes as $targetPropertyType) {
                if ('null' === $targetPropertyType->getBuiltinType()) {
                    $targetCanAcceptNull = true;

                    break;
                }
            }

            // determine if source property is lazy

            $sourceLazy = !in_array($sourceProperty, $eagerProperties, true);

            // instantiate property mapping

            $propertyMapping = new PropertyMapping(
                sourceProperty: ReadMode::None !== $sourceReadMode ? $sourceProperty : null,
                targetProperty: $targetProperty,
                sourceTypes: $sourcePropertyTypes,
                targetTypes: $targetPropertyTypes,
                sourceReadMode: $sourceReadMode,
                sourceReadName: $sourceReadName,
                sourceReadVisibility: $sourceReadVisibility,
                targetReadMode: $targetReadMode,
                targetReadName: $targetReadName,
                targetReadVisibility: $targetReadVisibility,
                targetSetterWriteMode: $targetSetterWriteMode,
                targetSetterWriteName: $targetSetterWriteName,
                targetRemoverWriteName: $targetRemoverWriteName,
                targetSetterWriteVisibility: $targetSetterWriteVisibility,
                targetRemoverWriteVisibility: $targetRemoverWriteVisibility,
                targetConstructorWriteMode: $targetConstructorWriteMode,
                targetConstructorWriteName: $targetConstructorWriteName,
                targetScalarType: $targetPropertyScalarType,
                propertyMapper: $serviceMethodSpecification,
                sourceLazy: $sourceLazy,
                targetCanAcceptNull: $targetCanAcceptNull,
                targetAllowsDelete: $targetAllowsDelete,
            );

            $propertyMappings[] = $propertyMapping;
            $effectivePropertiesToMap[] = $targetProperty;
        }

        $objectToObjectMetadata = new ObjectToObjectMetadata(
            sourceClass: $sourceClass,
            targetClass: $targetClass,
            providedTargetClass: $providedTargetClass,
            sourceAllowsDynamicProperties: $sourceAllowsDynamicProperties,
            targetAllowsDynamicProperties: $targetAllowsDynamicProperties,
            sourceProperties: $effectivePropertiesToMap,
            allPropertyMappings: $propertyMappings,
            instantiable: $instantiable,
            cloneable: $cloneable,
            initializableTargetPropertiesNotInSource: $initializableTargetPropertiesNotInSource,
            sourceModifiedTime: $sourceModifiedTime,
            targetModifiedTime: $targetModifiedTime,
            targetReadOnly: $targetReadOnly,
            constructorIsEager: false,
        );

        // create proxy if possible

        try {
            // ensure we can create the proxy

            $this->proxyFactory
                ->createProxy($targetClass, function ($instance): void {}, $eagerProperties);

            // determine if the constructor contains eager properties. if it
            // does, then the constructor is eager

            $constructorIsEager = false;

            foreach ($objectToObjectMetadata->getConstructorPropertyMappings() as $propertyMapping) {
                if (!$propertyMapping->isSourceLazy()) {
                    $constructorIsEager = true;

                    break;
                }
            }

            // if the constructor is eager, then every constructor argument is
            // eager

            if ($constructorIsEager) {
                foreach ($objectToObjectMetadata->getConstructorPropertyMappings() as $propertyMapping) {
                    $eagerProperties[] = $propertyMapping->getTargetProperty();
                }

                $eagerProperties = \array_unique($eagerProperties);
            }

            // skipped properties is the argument used by createLazyGhost()

            $skippedProperties = ClassUtil::getSkippedProperties(
                $targetClass,
                $eagerProperties
            );

            $objectToObjectMetadata = $objectToObjectMetadata->withTargetProxy(
                $skippedProperties,
                $constructorIsEager
            );
        } catch (ProxyNotSupportedException $e) {
            $objectToObjectMetadata = $objectToObjectMetadata
                ->withReasonCannotUseProxy($e->getReason());
        }

        return $objectToObjectMetadata;
    }

    /**
     * @param class-string $class
     *
     * @return array<int,string>
     */
    private function listProperties(
        string $class,
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        return array_values($properties);
    }

    /**
     * @param class-string $class
     *
     * @return array<int,string>
     */
    private function listInitializableProperties(
        string $class,
    ): array {
        $properties = $this->listProperties($class);

        $initializableProperties = [];

        foreach ($properties as $property) {
            if (true === $this->propertyInitializableExtractor->isInitializable($class, $property)) {
                $initializableProperties[] = $property;
            }
        }

        return $initializableProperties;
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private function allowsDynamicProperties(\ReflectionClass $class): bool
    {
        do {
            if ([] !== $class->getAttributes(\AllowDynamicProperties::class)) {
                return true;
            }
        } while ($class = $class->getParentClass());

        return false;
    }

    private function getConstructorWriteInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo {
        $writeInfo = $this->propertyWriteInfoExtractor
            ->getWriteInfo($class, $property, [
                'enable_getter_setter_extraction' => false,
                'enable_magic_methods_extraction' => false,
                'enable_adder_remover_extraction' => false,
            ]);

        if (null === $writeInfo) {
            return null;
        }

        if (PropertyWriteInfo::TYPE_CONSTRUCTOR === $writeInfo->getType()) {
            return $writeInfo;
        }

        return null;
    }

    private function getSetterWriteInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo {
        return $this->propertyWriteInfoExtractor
            ->getWriteInfo($class, $property, [
                'enable_constructor_extraction' => false,
            ]);
    }
}
