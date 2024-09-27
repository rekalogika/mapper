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

use Rekalogika\Mapper\Attribute\Eager;
use Rekalogika\Mapper\Attribute\InheritanceMap;
use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Transformer\Context\SourceClassAttributes;
use Rekalogika\Mapper\Transformer\Context\SourcePropertyAttributes;
use Rekalogika\Mapper\Transformer\Context\TargetClassAttributes;
use Rekalogika\Mapper\Transformer\Context\TargetPropertyAttributes;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\Exception\InternalClassUnsupportedException;
use Rekalogika\Mapper\Transformer\Exception\SourceClassNotInInheritanceMapException;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\ClassMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\ClassMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\PropertyMappingResolver;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\PropertyMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Util\PropertyMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
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
    private PropertyMetadataFactoryInterface $propertyMetadataFactory;

    private ClassMetadataFactoryInterface $classMetadataFactory;

    private PropertyMappingResolver $propertyMappingResolver;

    public function __construct(
        PropertyListExtractorInterface $propertyListExtractor,
        PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyMapperResolverInterface $propertyMapperResolver,
        PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
        EagerPropertiesResolverInterface $eagerPropertiesResolver,
        private ProxyFactoryInterface $proxyFactory,
        TypeResolverInterface $typeResolver,
    ) {
        $this->propertyMetadataFactory = new PropertyMetadataFactory(
            propertyReadInfoExtractor: $propertyReadInfoExtractor,
            propertyWriteInfoExtractor: $propertyWriteInfoExtractor,
            propertyTypeExtractor: $propertyTypeExtractor,
            typeResolver: $typeResolver,
        );

        $this->classMetadataFactory = new ClassMetadataFactory(
            eagerPropertiesResolver: $eagerPropertiesResolver,
        );

        $this->propertyMappingResolver = new PropertyMappingResolver(
            propertyListExtractor: $propertyListExtractor,
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
        $targetClass = $this->resolveTargetClass($sourceClass, $providedTargetClass);

        $sourceClassMetadata = $this->classMetadataFactory->createClassMetadata($sourceClass);
        $targetClassMetadata = $this->classMetadataFactory->createClassMetadata($targetClass);

        // internal classes are unsupported, except stdClass

        if (
            $sourceClassMetadata->isInternal() && !$sourceClassMetadata->hasReadableDynamicProperties()
        ) {
            throw new InternalClassUnsupportedException($sourceClass);
        }

        if (
            $targetClassMetadata->isInternal() && !$targetClassMetadata->hasWritableDynamicProperties()
        ) {
            throw new InternalClassUnsupportedException($targetClass);
        }

        // process properties mapping

        $propertyMappings = [];

        // determine properties to map

        $propertiesToMap = $this->propertyMappingResolver->getPropertiesToMap(
            sourceClass: $sourceClass,
            targetClass: $targetClass,
            targetAllowsDynamicProperties: $targetClassMetadata->hasWritableDynamicProperties(),
        );

        $eagerProperties = $sourceClassMetadata->getEagerProperties();

        // iterate over properties to map

        $effectivePropertiesToMap = [];

        foreach ($propertiesToMap as [$sourceProperty, $targetProperty]) {
            // service method specification

            $serviceMethodSpecification = $this->propertyMapperResolver
                ->getPropertyMapper($sourceClass, $targetClass, $targetProperty);

            // generate source & target property metadata

            $sourcePropertyMetadata = $this->propertyMetadataFactory
                ->createPropertyMetadata(
                    class: $sourceClass,
                    property: $sourceProperty,
                    allowsDynamicProperties: $sourceClassMetadata->hasReadableDynamicProperties(),
                );

            $targetPropertyMetadata = $this->propertyMetadataFactory
                ->createPropertyMetadata(
                    class: $targetClass,
                    property: $targetProperty,
                    allowsDynamicProperties: $targetClassMetadata->hasWritableDynamicProperties(),
                );

            // determine if source property is lazy

            $sourceLazy = !\in_array($sourceProperty, $eagerProperties, true);

            // attributes

            $sourceAttributes = new SourcePropertyAttributes($sourcePropertyMetadata->getAttributes());
            $targetAttributes = new TargetPropertyAttributes($targetPropertyMetadata->getAttributes());

            // instantiate property mapping

            $propertyMapping = new PropertyMapping(
                sourceProperty: $sourcePropertyMetadata->getReadMode() !== ReadMode::None ? $sourceProperty : null,
                targetProperty: $targetProperty,
                sourceTypes: $sourcePropertyMetadata->getTypes(),
                targetTypes: $targetPropertyMetadata->getTypes(),
                sourceReadMode: $sourcePropertyMetadata->getReadMode(),
                sourceReadName: $sourcePropertyMetadata->getReadName(),
                sourceReadVisibility: $sourcePropertyMetadata->getReadVisibility(),
                targetReadMode: $targetPropertyMetadata->getReadMode(),
                targetReadName: $targetPropertyMetadata->getReadName(),
                targetReadVisibility: $targetPropertyMetadata->getReadVisibility(),
                targetSetterWriteMode: $targetPropertyMetadata->getSetterWriteMode(),
                targetSetterWriteName: $targetPropertyMetadata->getSetterWriteName(),
                targetSetterVariadic: $targetPropertyMetadata->isSetterVariadic(),
                targetRemoverWriteName: $targetPropertyMetadata->getRemoverWriteName(),
                targetSetterWriteVisibility: $targetPropertyMetadata->getSetterWriteVisibility(),
                targetRemoverWriteVisibility: $targetPropertyMetadata->getRemoverWriteVisibility(),
                targetConstructorWriteMode: $targetPropertyMetadata->getConstructorWriteMode(),
                targetConstructorWriteName: $targetPropertyMetadata->getConstructorWriteName(),
                targetConstructorMandatory: $targetPropertyMetadata->isConstructorMandatory(),
                targetConstructorVariadic: $targetPropertyMetadata->isConstructorVariadic(),
                targetScalarType: $targetPropertyMetadata->getScalarType(),
                propertyMapper: $serviceMethodSpecification,
                sourceLazy: $sourceLazy,
                targetCanAcceptNull: $targetPropertyMetadata->isNullable(),
                targetReplaceable: $targetPropertyMetadata->isReplaceable(),
                targetImmutable: $targetPropertyMetadata->isImmutable(),
                sourceAttributes: $sourceAttributes,
                targetAttributes: $targetAttributes,
            );

            $propertyMappings[] = $propertyMapping;
            $effectivePropertiesToMap[] = $targetProperty;
        }

        $objectToObjectMetadata = new ObjectToObjectMetadata(
            sourceClass: $sourceClass,
            targetClass: $targetClass,
            providedTargetClass: $providedTargetClass,
            sourceAllowsDynamicProperties: $sourceClassMetadata->hasReadableDynamicProperties(),
            targetAllowsDynamicProperties: $targetClassMetadata->hasWritableDynamicProperties(),
            sourceProperties: $effectivePropertiesToMap,
            allPropertyMappings: $propertyMappings,
            instantiable: $targetClassMetadata->isInstantiable(),
            cloneable: $targetClassMetadata->isCloneable(),
            sourceModifiedTime: $sourceClassMetadata->getLastModified(),
            targetModifiedTime: $targetClassMetadata->getLastModified(),
            targetReadOnly: $targetClassMetadata->isReadonly(),
            constructorIsEager: false,
            sourceClassAttributes: new SourceClassAttributes($sourceClassMetadata->getAttributes()),
            targetClassAttributes: new TargetClassAttributes($targetClassMetadata->getAttributes()),
        );

        // if target is marked as eager, then we don't use proxy

        if ($objectToObjectMetadata->getTargetClassAttributes()->get(Eager::class) !== null) {
            $objectToObjectMetadata = $objectToObjectMetadata
                ->withReasonCannotUseProxy('Target class has Eager attribute');

            return $objectToObjectMetadata;
        }

        // create proxy if possible

        try {
            [$skippedProperties, $constructorIsEager] = $this->getProxyParameters(
                targetClass: $targetClass,
                eagerProperties: $eagerProperties,
                constructorPropertyMappings: $objectToObjectMetadata
                    ->getConstructorPropertyMappings(),
            );

            $objectToObjectMetadata = $objectToObjectMetadata->withTargetProxy(
                targetProxySkippedProperties: $skippedProperties,
                constructorIsEager: $constructorIsEager,
            );
        } catch (ProxyNotSupportedException $e) {
            $objectToObjectMetadata = $objectToObjectMetadata
                ->withReasonCannotUseProxy($e->getReason());
        }

        return $objectToObjectMetadata;
    }

    /**
     * @param class-string $targetClass
     * @param list<string> $eagerProperties
     * @param list<PropertyMapping> $constructorPropertyMappings
     * @return array{array<string,true>,bool}
     */
    private function getProxyParameters(
        string $targetClass,
        array $eagerProperties,
        array $constructorPropertyMappings,
    ): array {
        // ensure we can create the proxy

        $this->proxyFactory
            ->createProxy($targetClass, function ($instance): void {}, $eagerProperties);

        // determine if the constructor contains eager properties. if it
        // does, then the constructor is eager

        $constructorIsEager = false;

        foreach ($constructorPropertyMappings as $propertyMapping) {
            if (!$propertyMapping->isSourceLazy()) {
                $constructorIsEager = true;
                break;
            }
        }

        // if the constructor is eager, then every constructor argument is
        // eager

        if ($constructorIsEager) {
            foreach ($constructorPropertyMappings as $propertyMapping) {
                $eagerProperties[] = $propertyMapping->getTargetProperty();
            }

            $eagerProperties = array_unique($eagerProperties);
        }

        // skipped properties is the argument used by createLazyGhost()

        $skippedProperties = ClassUtil::getSkippedProperties(
            $targetClass,
            $eagerProperties,
        );

        return [$skippedProperties, $constructorIsEager];
    }
}
