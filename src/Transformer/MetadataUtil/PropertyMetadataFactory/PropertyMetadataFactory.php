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

use Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\DynamicPropertiesDeterminerInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\Model\PropertyMetadata;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyAccessInfoExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\UnalterableDeterminerInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\TypeInfo\Type;

/**
 * @internal
 */
final readonly class PropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private PropertyMetadataFactoryInterface $propertyPathMetadataFactory;

    public function __construct(
        private PropertyAccessInfoExtractorInterface $propertyAccessInfoExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private TypeResolverInterface $typeResolver,
        private DynamicPropertiesDeterminerInterface $dynamicPropertiesDeterminer,
        private AttributesExtractorInterface $attributesExtractor,
        private UnalterableDeterminerInterface $unalterableDeterminer,
    ) {
        $this->propertyPathMetadataFactory = new PropertyPathMetadataFactory(
            propertyTypeExtractor: $propertyTypeExtractor,
            propertyAccessInfoExtractor: $this->propertyAccessInfoExtractor,
            attributesExtractor: $this->attributesExtractor,
            unalterableDeterminer: $this->unalterableDeterminer,
        );

    }

    /**
     * @param class-string $class
     * @todo collect property path attributes
     */
    #[\Override]
    public function createPropertyMetadata(
        string $class,
        string $property,
    ): PropertyMetadata {

        // property path

        if ($this->isPropertyPath($property)) {
            return $this->propertyPathMetadataFactory->createPropertyMetadata(
                class: $class,
                property: $property,
            );
        }

        // normal, non property path

        $readInfo = $this->propertyAccessInfoExtractor
            ->getReadInfo($class, $property);

        $writeInfo = $this->propertyAccessInfoExtractor
            ->getWriteInfo($class, $property);

        $constructorWriteInfo = $this->propertyAccessInfoExtractor
            ->getConstructorInfo($class, $property);

        [$readMode, $readName, $readVisibility] = $this->processPropertyReadInfo(
            class: $class,
            property: $property,
            readInfo: $readInfo,
        );

        [$constructorWriteMode, $constructorWriteName] =
            $this->processConstructorWriteInfo($constructorWriteInfo);

        $constructorMandatory =
            $constructorWriteInfo !== null
            && $this->isConstructorMandatory(
                class: $class,
                constructorArgument: $constructorWriteInfo->getName(),
            );

        $constructorVariadic =
            $constructorWriteInfo !== null
            && $this->isConstructorArgumentVariadic(
                class: $class,
                argument: $constructorWriteInfo->getName(),
            );

        [
            $setterWriteMode,
            $setterWriteName,
            $setterWriteVisibility,
            $removerWriteName,
            $removerWriteVisibility,
            $replaceable,
            $mutableByHost,
        ]
            = $this->processPropertyWriteInfo(
                class: $class,
                property: $property,
                readInfo: $readInfo,
                writeInfo: $writeInfo,
            );

        [$types, $scalarType, $nullable] =
            $this->getPropertyTypes($class, $property);

        $setterVariadic =
            $setterWriteMode === WriteMode::Method
            && $setterWriteName !== null
            && $this->isSetterVariadic(
                class: $class,
                setter: $setterWriteName,
            );

        $unalterable = $this->unalterableDeterminer
            ->isTypesUnalterable($types);

        $attributes = $this->attributesExtractor->getPropertyAttributes(
            class: $class,
            property: $property,
        );

        return new PropertyMetadata(
            readMode: $readMode,
            readName: $readName,
            readVisibility: $readVisibility,
            constructorWriteMode: $constructorWriteMode,
            constructorWriteName: $constructorWriteName,
            constructorMandatory: $constructorMandatory,
            constructorVariadic: $constructorVariadic,
            setterWriteMode: $setterWriteMode,
            setterWriteName: $setterWriteName,
            setterWriteVisibility: $setterWriteVisibility,
            setterVariadic: $setterVariadic,
            removerWriteName: $removerWriteName,
            removerWriteVisibility: $removerWriteVisibility,
            types: $types,
            scalarType: $scalarType,
            nullable: $nullable,
            replaceable: $replaceable,
            unalterable: $unalterable,
            mutableByHost: $mutableByHost,
            attributes: $attributes,
        );
    }

    /**
     * @param class-string $class
     * @return array{ReadMode,?string,Visibility}
     */
    private function processPropertyReadInfo(
        string $class,
        string $property,
        ?PropertyReadInfo $readInfo,
    ): array {
        $allowsDynamicProperties = $this->dynamicPropertiesDeterminer
            ->allowsDynamicProperties($class);

        if ($readInfo === null) {
            // if source allows dynamic properties, including stdClass
            if ($allowsDynamicProperties) {
                $readMode = ReadMode::DynamicProperty;
                $readName = $property;
                $readVisibility = Visibility::Public;
            } else {
                $readMode = ReadMode::None;
                $readName = null;
                $readVisibility = Visibility::None;
            }
        } else {
            $readMode = match ($readInfo->getType()) {
                PropertyReadInfo::TYPE_METHOD => ReadMode::Method,
                PropertyReadInfo::TYPE_PROPERTY => ReadMode::Property,
                default => ReadMode::None,
            };

            $readName = $readInfo->getName();

            $readVisibility = match ($readInfo->getVisibility()) {
                PropertyReadInfo::VISIBILITY_PUBLIC => Visibility::Public,
                PropertyReadInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                PropertyReadInfo::VISIBILITY_PRIVATE => Visibility::Private,
                default => Visibility::None,
            };
        }

        return [$readMode, $readName, $readVisibility];
    }

    /**
     * @return array{WriteMode,?string}
     */
    private function processConstructorWriteInfo(
        ?PropertyWriteInfo $constructorWriteInfo,
    ): array {
        if (
            $constructorWriteInfo === null
            || $constructorWriteInfo->getType() !== PropertyWriteInfo::TYPE_CONSTRUCTOR
        ) {
            $constructorWriteMode = WriteMode::None;
            $constructorWriteName = null;
        } else {
            $constructorWriteMode = WriteMode::Constructor;
            $constructorWriteName = $constructorWriteInfo->getName();
        }

        return [$constructorWriteMode, $constructorWriteName];
    }

    /**
     * @param class-string $class
     * @return array{WriteMode,?string,Visibility,?string,Visibility,bool,bool}
     */
    private function processPropertyWriteInfo(
        string $class,
        string $property,
        ?PropertyReadInfo $readInfo,
        ?PropertyWriteInfo $writeInfo,
    ): array {
        $allowsDynamicProperties = $this->dynamicPropertiesDeterminer
            ->allowsDynamicProperties($class);

        $removerWriteName = null;
        $removerWriteVisibility = Visibility::None;

        if ($writeInfo === null) {
            $replaceable = false;
            $mutableByHost = false;
            $setterWriteMode = WriteMode::None;
            $setterWriteName = null;
            $setterWriteVisibility = Visibility::None;
        } elseif ($writeInfo->getType() === PropertyWriteInfo::TYPE_ADDER_AND_REMOVER) {
            $replaceable = false;
            $mutableByHost = true;
            $setterWriteMode = WriteMode::AdderRemover;
            $setterWriteName = $writeInfo->getAdderInfo()->getName();
            $removerWriteName = $writeInfo->getRemoverInfo()->getName();
            $setterWriteVisibility = match ($writeInfo->getAdderInfo()->getVisibility()) {
                PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                default => Visibility::None,
            };
            $removerWriteVisibility = match ($writeInfo->getRemoverInfo()->getVisibility()) {
                PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                default => Visibility::None,
            };
        } else {
            $setterWriteMode = match ($writeInfo->getType()) {
                PropertyWriteInfo::TYPE_METHOD => WriteMode::Method,
                PropertyWriteInfo::TYPE_PROPERTY => WriteMode::Property,
                default => WriteMode::None,
            };

            if ($setterWriteMode === WriteMode::None) {
                if ($allowsDynamicProperties && $readInfo === null) {
                    $setterWriteMode = WriteMode::DynamicProperty;
                    $setterWriteName = $property;
                    $setterWriteVisibility = Visibility::Public;
                    $replaceable = true;
                    $mutableByHost = false;
                } else {
                    $setterWriteName = null;
                    $setterWriteVisibility = Visibility::None;
                    $replaceable = false;
                    $mutableByHost = false;
                }
            } else {
                $setterWriteName = $writeInfo->getName();
                $setterWriteVisibility = match ($writeInfo->getVisibility()) {
                    PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                    PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                    PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                    default => Visibility::None,
                };
                $replaceable = $setterWriteVisibility === Visibility::Public;
                $mutableByHost = false;
            }
        }

        return [
            $setterWriteMode,
            $setterWriteName,
            $setterWriteVisibility,
            $removerWriteName,
            $removerWriteVisibility,
            $replaceable,
            $mutableByHost,
        ];
    }

    /**
     * @param class-string $class
     * @return array{list<Type>,'int'|'float'|'string'|'bool'|'null'|null,bool}
     */
    private function getPropertyTypes(string $class, string $property): array
    {
        // break property types into simple types

        // TypeInfo's resolver is stricter than legacy property-info — for
        // example, it rejects `array<mixed,mixed>` as an invalid array key.
        // On failure, fall back to the property's own type declaration so we
        // still get useful type info.
        try {
            $originalPropertyType = $this->propertyTypeExtractor
                ->getType($class, $property);
        } catch (\Symfony\Component\TypeInfo\Exception\InvalidArgumentException) {
            $originalPropertyType = $this->getDeclaredPropertyType($class, $property);
        }

        // Workaround for a property-info behavior change: getType() now
        // returns BuiltinType<MIXED> when the accessor method declares a
        // `mixed` return type, whereas the legacy getTypes() filtered out
        // `mixed` and fell back to the property's own type declaration.
        // Restore the legacy behavior by preferring the property declaration
        // when it exists.
        if (TypeCheck::isMixed($originalPropertyType)) {
            $declared = $this->getDeclaredPropertyType($class, $property);
            if ($declared !== null) {
                $originalPropertyType = $declared;
            }
        }

        $types = [];

        if ($originalPropertyType !== null) {
            $simpleTypes = $this->typeResolver->getSimpleTypes($originalPropertyType);

            foreach ($simpleTypes as $simpleType) {
                if (TypeCheck::isMixed($simpleType)) {
                    continue;
                }

                $types[] = $simpleType;
            }
        }

        // determine if it is a lone scalar type

        /** @var 'int'|'float'|'string'|'bool'|'null'|null */
        $scalarType = Util::determineScalarType(
            $originalPropertyType !== null ? [$originalPropertyType] : [],
        );

        // determine if nullable

        $nullable = $originalPropertyType?->isNullable() ?? false;

        return [$types, $scalarType, $nullable];
    }

    private function isPropertyPath(string $property): bool
    {
        return str_contains($property, '.') || str_contains($property, '[');
    }

    /**
     * Returns the type from the property's own type declaration via reflection,
     * if the property exists and has a typed declaration. Used as a fallback
     * when the property-info extractor returns a useless `mixed` type from a
     * loosely-typed accessor.
     *
     * @param class-string $class
     */
    private function getDeclaredPropertyType(string $class, string $property): ?Type
    {
        try {
            $reflectionProperty = new \ReflectionProperty($class, $property);
        } catch (\ReflectionException) {
            return null;
        }

        $reflectionType = $reflectionProperty->getType();

        if (!$reflectionType instanceof \ReflectionNamedType) {
            return null;
        }

        $name = $reflectionType->getName();

        if ($name === 'mixed' || $name === 'never' || $name === 'void') {
            return null;
        }

        if ($reflectionType->isBuiltin()) {
            try {
                $type = Type::builtin($name);
            } catch (\Throwable) {
                return null;
            }
        } else {
            if (!class_exists($name) && !interface_exists($name) && !enum_exists($name)) {
                return null;
            }

            /** @var class-string $name */
            $type = Type::object($name);
        }

        return $reflectionType->allowsNull() && $name !== 'null'
            ? Type::nullable($type)
            : $type;
    }

    /**
     * @param class-string $class
     */
    private function isConstructorMandatory(
        string $class,
        string $constructorArgument,
    ): bool {
        $constructor = new \ReflectionMethod($class, '__construct');
        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->getName() === $constructorArgument) {
                return !$parameter->isDefaultValueAvailable();
            }
        }

        return false;
    }

    /**
     * @param class-string $class
     */
    private function isConstructorArgumentVariadic(
        string $class,
        string $argument,
    ): bool {
        $reflectionMethod = new \ReflectionMethod($class, '__construct');
        $reflectionParameters = $reflectionMethod->getParameters();

        foreach ($reflectionParameters as $reflectionParameter) {
            if ($reflectionParameter->getName() === $argument) {
                return $reflectionParameter->isVariadic();
            }
        }

        return false;
    }

    /**
     * @param class-string $class
     */
    private function isSetterVariadic(
        string $class,
        string $setter,
    ): bool {
        $reflectionMethod = new \ReflectionMethod($class, $setter);
        $reflectionParameters = $reflectionMethod->getParameters();
        $reflectionParameter = $reflectionParameters[0];
        return $reflectionParameter->isVariadic();
    }
}
