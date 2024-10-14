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
use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Transformer\Context\SourceClassAttributes;
use Rekalogika\Mapper\Transformer\Context\SourcePropertyAttributes;
use Rekalogika\Mapper\Transformer\Context\TargetClassAttributes;
use Rekalogika\Mapper\Transformer\Context\TargetPropertyAttributes;
use Rekalogika\Mapper\Transformer\Exception\InternalClassUnsupportedException;
use Rekalogika\Mapper\Transformer\MetadataUtil\ClassMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyMappingResolverInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\TargetClassResolverInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMappingMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final readonly class ObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    public function __construct(
        private PropertyMapperResolverInterface $propertyMapperResolver,
        private ProxyFactoryInterface $proxyFactory,
        private PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private ClassMetadataFactoryInterface $classMetadataFactory,
        private PropertyMappingResolverInterface $propertyMappingResolver,
        private TargetClassResolverInterface $targetClassResolver,
    ) {}

    #[\Override]
    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
    ): ObjectToObjectMetadata {
        $providedTargetClass = $targetClass;

        $targetClass = $this->targetClassResolver
            ->resolveTargetClass($sourceClass, $providedTargetClass);

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
                );

            $targetPropertyMetadata = $this->propertyMetadataFactory
                ->createPropertyMetadata(
                    class: $targetClass,
                    property: $targetProperty,
                );

            // determine if source property is lazy

            $sourceLazy = !\in_array($sourceProperty, $eagerProperties, true);

            // attributes

            $sourceAttributes = new SourcePropertyAttributes($sourcePropertyMetadata->getAttributes());
            $targetAttributes = new TargetPropertyAttributes($targetPropertyMetadata->getAttributes());

            // generate id

            $id = hash('sha256', $sourceClass . $sourceProperty . $targetClass . $targetProperty);

            // instantiate property mapping

            $propertyMapping = new PropertyMappingMetadata(
                id: $id,
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
                targetUnalterable: $targetPropertyMetadata->isUnalterable(),
                hostCanMutateTarget: $targetPropertyMetadata->isMutableByHost(),
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
            allTargetClasses: ClassUtil::getAllClassesFromObject($targetClass),
            sourceAllowsDynamicProperties: $sourceClassMetadata->hasReadableDynamicProperties(),
            targetAllowsDynamicProperties: $targetClassMetadata->hasWritableDynamicProperties(),
            sourceProperties: $effectivePropertiesToMap,
            allPropertyMappings: $propertyMappings,
            instantiable: $targetClassMetadata->isInstantiable(),
            cloneable: $targetClassMetadata->isCloneable(),
            targetUnalterable: $targetClassMetadata->isUnalterable(),
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
     * @param array<string,PropertyMappingMetadata> $constructorPropertyMappings
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
