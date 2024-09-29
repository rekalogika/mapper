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

use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Model\PropertyMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\ClassUtil;
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class PropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private PropertyMetadataFactoryInterface $propertyPathMetadataFactory;

    public function __construct(
        private PropertyAccessInfoExtractor $propertyAccessInfoExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private TypeResolverInterface $typeResolver,
        private DynamicPropertiesDeterminer $dynamicPropertiesDeterminer,
    ) {
        $this->propertyPathMetadataFactory = new PropertyPathMetadataFactory(
            propertyTypeExtractor: $propertyTypeExtractor,
            propertyAccessInfoExtractor: $this->propertyAccessInfoExtractor,
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

        $attributes = $this->getPropertyAttributes(
            class: $class,
            property: $property,
            readInfo: $readInfo,
            writeInfo: $writeInfo,
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
            immutable: TypeCheck::isRecursivelyImmutable($types),
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
     * @return array{WriteMode,?string,Visibility,?string,Visibility,bool}
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
            $setterWriteMode = WriteMode::None;
            $setterWriteName = null;
            $setterWriteVisibility = Visibility::None;
        } elseif ($writeInfo->getType() === PropertyWriteInfo::TYPE_ADDER_AND_REMOVER) {
            $replaceable = false;
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
                } else {
                    $setterWriteName = null;
                    $setterWriteVisibility = Visibility::None;
                    $replaceable = false;
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
            }
        }

        return [
            $setterWriteMode,
            $setterWriteName,
            $setterWriteVisibility,
            $removerWriteName,
            $removerWriteVisibility,
            $replaceable,
        ];
    }

    /**
     * @param class-string $class
     * @return list<object>
     */
    private function getPropertyAttributes(
        string $class,
        string $property,
        ?PropertyReadInfo $readInfo,
        ?PropertyWriteInfo $writeInfo,
    ): array {
        $methods = [];

        // getter

        if (
            $readInfo !== null
            && $readInfo->getType() === PropertyReadInfo::TYPE_METHOD
        ) {
            $methods[] = $readInfo->getName();
        }

        // mutators

        if ($writeInfo !== null) {
            if ($writeInfo->getType() === PropertyWriteInfo::TYPE_METHOD) {
                $methods[] = $writeInfo->getName();
            } elseif ($writeInfo->getType() === PropertyWriteInfo::TYPE_ADDER_AND_REMOVER) {
                try {
                    $adderInfo = $writeInfo->getAdderInfo();
                    $methods[] = $adderInfo->getName();
                } catch (\LogicException) {
                    // ignore
                }

                try {
                    $removerInfo = $writeInfo->getRemoverInfo();
                    $methods[] = $removerInfo->getName();
                } catch (\LogicException) {
                    // ignore
                }
            }
        }

        return ClassUtil::getPropertyAttributes(
            class: $class,
            property: $property,
            attributeClass: null,
            methods: $methods,
        );
    }

    /**
     * @param class-string $class
     * @return array{list<Type>,'int'|'float'|'string'|'bool'|'null'|null,bool}
     */
    private function getPropertyTypes(string $class, string $property): array
    {
        // break property types into simple types

        $originalPropertyTypes = $this->propertyTypeExtractor
            ->getTypes($class, $property) ?? [];

        $originalPropertyTypes = array_values($originalPropertyTypes);

        $types = [];

        foreach ($originalPropertyTypes as $propertyType) {
            $simpleTypes = $this->typeResolver->getSimpleTypes($propertyType);

            foreach ($simpleTypes as $simpleType) {
                if ($simpleType instanceof MixedType) {
                    continue;
                }

                $types[] = $simpleType;
            }
        }

        // determine if it is a lone scalar type

        /** @var 'int'|'float'|'string'|'bool'|'null'|null */
        $scalarType = Util::determineScalarType($originalPropertyTypes);

        // determine if nullable

        $nullable = false;

        foreach ($types as $type) {
            if ($type->getBuiltinType() === 'null') {
                $nullable = true;
                break;
            }
        }

        return [$types, $scalarType, $nullable];
    }

    private function isPropertyPath(string $property): bool
    {
        return str_contains($property, '.') || str_contains($property, '[');
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
