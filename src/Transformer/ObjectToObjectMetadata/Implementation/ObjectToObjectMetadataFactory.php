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
use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\Exception\SourceClassNotInInheritanceMapException;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Util\PropertyMappingResolver;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Util\PropertyMetadataResolver;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

/**
 * @internal
 */
final readonly class ObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    private PropertyMetadataResolver $propertyMetadataResolver;

    public function __construct(
        private PropertyListExtractorInterface $propertyListExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyMapperResolverInterface $propertyMapperResolver,
        PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
        private EagerPropertiesResolverInterface $eagerPropertiesResolver,
        private ProxyFactoryInterface $proxyFactory,
        private TypeResolverInterface $typeResolver,
    ) {
        $this->propertyMetadataResolver = new PropertyMetadataResolver(
            propertyReadInfoExtractor: $propertyReadInfoExtractor,
            propertyWriteInfoExtractor: $propertyWriteInfoExtractor,
        );
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @return class-string
     */
    private function resolveTargetClass(
        string $sourceClass,
        string $targetClass,
    ): string {
        $sourceReflection = new \ReflectionClass($sourceClass);
        $targetReflection = new \ReflectionClass($targetClass);

        $targetAttributes = $targetReflection->getAttributes(InheritanceMap::class);

        if ($targetAttributes !== []) {
            // if the target has an InheritanceMap, we try to resolve the target
            // class using the InheritanceMap

            $inheritanceMap = $targetAttributes[0]->newInstance();

            $resolvedTargetClass = $inheritanceMap->getTargetClassFromSourceClass($sourceClass);

            if ($resolvedTargetClass === null) {
                throw new SourceClassNotInInheritanceMapException($sourceClass, $targetClass);
            }

            return $resolvedTargetClass;
        } elseif ($targetReflection->isAbstract() || $targetReflection->isInterface()) {
            // if target doesn't have an inheritance map, but is also abstract
            // or an interface, we try to find the InheritanceMap from the
            // source

            $sourceClasses = ClassUtil::getAllClassesFromObject($sourceClass);

            foreach ($sourceClasses as $currentSourceClass) {
                $sourceReflection = new \ReflectionClass($currentSourceClass);
                $sourceAttributes = $sourceReflection->getAttributes(InheritanceMap::class);

                if ($sourceAttributes !== []) {
                    $inheritanceMap = $sourceAttributes[0]->newInstance();

                    $resolvedTargetClass = $inheritanceMap->getSourceClassFromTargetClass($sourceClass);

                    if ($resolvedTargetClass === null) {
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

        $sourceAllowsDynamicProperties =
            $this->allowsDynamicProperties($sourceReflection)
            || method_exists($sourceClass, '__get');

        $targetAllowsDynamicProperties =
            $this->allowsDynamicProperties($targetReflection)
            || method_exists($targetClass, '__set');

        // queries

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

        $propertiesToMap = PropertyMappingResolver::resolvePropertiesToMap(
            propertyListExtractor: $this->propertyListExtractor,
            sourceClass: $sourceClass,
            targetClass: $targetClass,
            targetAllowsDynamicProperties: $targetAllowsDynamicProperties,
        );

        // iterate over properties to map

        $effectivePropertiesToMap = [];

        foreach ($propertiesToMap as [$sourceProperty, $targetProperty]) {
            // service method specification

            $serviceMethodSpecification = $this->propertyMapperResolver
                ->getPropertyMapper($sourceClass, $targetClass, $targetProperty);

            // generate source & target property metadata

            $sourcePropertyMetadata = $this->propertyMetadataResolver
                ->createSourcePropertyMetadata(
                    class: $sourceClass,
                    property: $sourceProperty,
                    allowsDynamicProperties: $sourceAllowsDynamicProperties,
                );

            $targetPropertyMetadata = $this->propertyMetadataResolver
                ->createTargetPropertyMetadata(
                    class: $targetClass,
                    property: $targetProperty,
                    allowsDynamicProperties: $targetAllowsDynamicProperties,
                );

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

            /** @var 'int'|'float'|'string'|'bool'|'null'|null */
            $targetPropertyScalarType = null;

            if (\count($originalTargetPropertyTypes) === 1) {
                $targetPropertyType = $originalTargetPropertyTypes[0];
                $targetPropertyBuiltInType = $targetPropertyType->getBuiltinType();

                if (\in_array(
                    $targetPropertyBuiltInType,
                    ['int', 'float', 'string', 'bool', 'null'],
                    true,
                )) {
                    $targetPropertyScalarType = $targetPropertyBuiltInType;
                }
            }

            // determine if target can accept null

            $targetCanAcceptNull = false;

            foreach ($targetPropertyTypes as $targetPropertyType) {
                if ($targetPropertyType->getBuiltinType() === 'null') {
                    $targetCanAcceptNull = true;
                    break;
                }
            }

            // determine if source property is lazy

            $sourceLazy = !\in_array($sourceProperty, $eagerProperties, true);

            // instantiate property mapping

            $propertyMapping = new PropertyMapping(
                sourceProperty: $sourcePropertyMetadata->getReadMode() !== ReadMode::None ? $sourceProperty : null,
                targetProperty: $targetProperty,
                sourceTypes: $sourcePropertyTypes,
                targetTypes: $targetPropertyTypes,
                sourceReadMode: $sourcePropertyMetadata->getReadMode(),
                sourceReadName: $sourcePropertyMetadata->getReadName(),
                sourceReadVisibility: $sourcePropertyMetadata->getReadVisibility(),
                targetReadMode: $targetPropertyMetadata->getReadMode(),
                targetReadName: $targetPropertyMetadata->getReadName(),
                targetReadVisibility: $targetPropertyMetadata->getReadVisibility(),
                targetSetterWriteMode: $targetPropertyMetadata->getSetterWriteMode(),
                targetSetterWriteName: $targetPropertyMetadata->getSetterWriteName(),
                targetRemoverWriteName: $targetPropertyMetadata->getRemoverWriteName(),
                targetSetterWriteVisibility: $targetPropertyMetadata->getSetterWriteVisibility(),
                targetRemoverWriteVisibility: $targetPropertyMetadata->getRemoverWriteVisibility(),
                targetConstructorWriteMode: $targetPropertyMetadata->getConstructorWriteMode(),
                targetConstructorWriteName: $targetPropertyMetadata->getConstructorWriteName(),
                targetScalarType: $targetPropertyScalarType,
                propertyMapper: $serviceMethodSpecification,
                sourceLazy: $sourceLazy,
                targetCanAcceptNull: $targetCanAcceptNull,
                targetAllowsDelete: $targetPropertyMetadata->allowsDelete() || $sourcePropertyMetadata->allowsTargetDelete(),
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

                $eagerProperties = array_unique($eagerProperties);
            }

            // skipped properties is the argument used by createLazyGhost()

            $skippedProperties = ClassUtil::getSkippedProperties(
                $targetClass,
                $eagerProperties,
            );

            $objectToObjectMetadata = $objectToObjectMetadata->withTargetProxy(
                $skippedProperties,
                $constructorIsEager,
            );
        } catch (ProxyNotSupportedException $e) {
            $objectToObjectMetadata = $objectToObjectMetadata
                ->withReasonCannotUseProxy($e->getReason());
        }

        return $objectToObjectMetadata;
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
}
