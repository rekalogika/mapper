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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Util;

use Rekalogika\Mapper\Attribute\AllowDelete;
use Rekalogika\Mapper\Attribute\AllowTargetDelete;
use Rekalogika\Mapper\Transformer\Exception\InternalClassUnsupportedException;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\SourcePropertyMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\TargetPropertyMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\WriteMode;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class PropertyMetadataResolver
{
    public function __construct(
        private PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        private PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private TypeResolverInterface $typeResolver,
    ) {}

    /**
     * @param class-string $class
     */
    public function createSourcePropertyMetadata(
        string $class,
        string $property,
        bool $allowsDynamicProperties,
    ): SourcePropertyMetadata {
        $reflectionClass = new \ReflectionClass($class);

        $readInfo = $this->propertyReadInfoExtractor
            ->getReadInfo($class, $property);

        if (!$allowsDynamicProperties && $reflectionClass->isInternal()) {
            throw new InternalClassUnsupportedException($class);
        }

        [$readMode, $readName, $readVisibility] = $this->getPropertyReadInfo(
            readInfo: $readInfo,
            property: $property,
            allowsDynamicProperties: $allowsDynamicProperties,
        );

        $allowsTargetDelete = $this->sourceAllowsTargetDelete(
            class: $class,
            property: $property,
            readInfo: $readInfo,
        );

        [$types] = $this->getPropertyTypes($class, $property);

        return new SourcePropertyMetadata(
            readMode: $readMode,
            readName: $readName,
            readVisibility: $readVisibility,
            allowsTargetDelete: $allowsTargetDelete,
            types: $types,
        );
    }

    /**
     * @param class-string $class
     */
    public function createTargetPropertyMetadata(
        string $class,
        string $property,
        bool $allowsDynamicProperties,
    ): TargetPropertyMetadata {
        $reflectionClass = new \ReflectionClass($class);

        $readInfo = $this->propertyReadInfoExtractor
            ->getReadInfo($class, $property);

        $writeInfo = $this
            ->getSetterPropertyWriteInfo($class, $property);

        $constructorWriteInfo = $this
            ->getConstructorPropertyWriteInfo($class, $property);

        if (!$allowsDynamicProperties && $reflectionClass->isInternal()) {
            throw new InternalClassUnsupportedException($class);
        }

        [$readMode, $readName, $readVisibility] = $this->getPropertyReadInfo(
            readInfo: $readInfo,
            property: $property,
            allowsDynamicProperties: $allowsDynamicProperties,
        );

        [$constructorWriteMode, $constructorWriteName] =
            $this->getConstructorWriteInfo($constructorWriteInfo);

        [$setterWriteMode, $setterWriteName, $setterWriteVisibility, $removerWriteName, $removerWriteVisibility,] = $this->getPropertyWriteInfo(
            readInfo: $readInfo,
            writeInfo: $writeInfo,
            property: $property,
            allowsDynamicProperties: $allowsDynamicProperties,
        );

        $allowsDelete = $this->targetAllowsDelete(
            class: $class,
            property: $property,
            readInfo: $readInfo,
            writeInfo: $writeInfo,
        );

        [$types, $scalarType, $nullable] =
            $this->getPropertyTypes($class, $property);

        return new TargetPropertyMetadata(
            readMode: $readMode,
            readName: $readName,
            readVisibility: $readVisibility,
            constructorWriteMode: $constructorWriteMode,
            constructorWriteName: $constructorWriteName,
            setterWriteMode: $setterWriteMode,
            setterWriteName: $setterWriteName,
            setterWriteVisibility: $setterWriteVisibility,
            removerWriteName: $removerWriteName,
            removerWriteVisibility: $removerWriteVisibility,
            allowsDelete: $allowsDelete,
            types: $types,
            scalarType: $scalarType,
            nullable: $nullable,
        );
    }

    /**
     * @return array{ReadMode,?string,Visibility}
     */
    private function getPropertyReadInfo(
        ?PropertyReadInfo $readInfo,
        string $property,
        bool $allowsDynamicProperties,
    ): array {
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
    private function getConstructorWriteInfo(
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
     * @return array{WriteMode,?string,Visibility,?string,Visibility}
     */
    private function getPropertyWriteInfo(
        ?PropertyReadInfo $readInfo,
        ?PropertyWriteInfo $writeInfo,
        string $property,
        bool $allowsDynamicProperties,
    ): array {
        $removerWriteName = null;
        $removerWriteVisibility = Visibility::None;

        if ($writeInfo === null) {
            $setterWriteMode = WriteMode::None;
            $setterWriteName = null;
            $setterWriteVisibility = Visibility::None;
        } elseif ($writeInfo->getType() === PropertyWriteInfo::TYPE_ADDER_AND_REMOVER) {
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
                } else {
                    $setterWriteName = null;
                    $setterWriteVisibility = Visibility::None;
                }
            } else {
                $setterWriteName = $writeInfo->getName();
                $setterWriteVisibility = match ($writeInfo->getVisibility()) {
                    PropertyWriteInfo::VISIBILITY_PUBLIC => Visibility::Public,
                    PropertyWriteInfo::VISIBILITY_PROTECTED => Visibility::Protected,
                    PropertyWriteInfo::VISIBILITY_PRIVATE => Visibility::Private,
                    default => Visibility::None,
                };
            }
        }

        return [
            $setterWriteMode,
            $setterWriteName,
            $setterWriteVisibility,
            $removerWriteName,
            $removerWriteVisibility,
        ];
    }

    /**
     * @param class-string $class
     * @return boolean
     */
    private function sourceAllowsTargetDelete(
        string $class,
        string $property,
        ?PropertyReadInfo $readInfo,
    ): bool {
        $sourceMethods = [];

        $sourceGetter = $readInfo !== null
            ? $readInfo->getName()
            : null;

        if ($sourceGetter !== null) {
            $sourceMethods[] = $sourceGetter;
        }

        $allowTargetDeleteAttributes = ClassUtil::getAttributes(
            class: $class,
            property: $property,
            attributeClass: AllowTargetDelete::class,
            methods: $sourceMethods,
        );

        return $allowTargetDeleteAttributes !== [];
    }

    /**
     * @param class-string $class
     * @return boolean
     */
    private function targetAllowsDelete(
        string $class,
        string $property,
        ?PropertyReadInfo $readInfo,
        ?PropertyWriteInfo $writeInfo,
    ): bool {
        $targetMethods = [];

        $targetGetter = $readInfo !== null
            ? $readInfo->getName()
            : null;

        if ($targetGetter !== null) {
            $targetMethods[] = $targetGetter;
        }

        $targetRemover =
            (
                $writeInfo !== null &&
                $writeInfo->getType() === PropertyWriteInfo::TYPE_ADDER_AND_REMOVER
            )
            ? $writeInfo->getRemoverInfo()->getName()
            : null;

        if ($targetRemover !== null) {
            $targetMethods[] = $targetRemover;
        }

        $allowDeleteAttributes = ClassUtil::getAttributes(
            class: $class,
            property: $property,
            attributeClass: AllowDelete::class,
            methods: $targetMethods,
        );

        return $allowDeleteAttributes !== [];
    }

    /**
     * @param class-string $class
     */
    private function getSetterPropertyWriteInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo {
        return $this->propertyWriteInfoExtractor->getWriteInfo(
            class: $class,
            property: $property,
            context: [
                'enable_constructor_extraction' => false,
            ],
        );
    }

    private function getConstructorPropertyWriteInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo {
        $writeInfo = $this->propertyWriteInfoExtractor->getWriteInfo(
            class: $class,
            property: $property,
            context: [
                'enable_getter_setter_extraction' => false,
                'enable_magic_methods_extraction' => false,
                'enable_adder_remover_extraction' => false,
            ],
        );

        if ($writeInfo === null) {
            return null;
        }

        if ($writeInfo->getType() === PropertyWriteInfo::TYPE_CONSTRUCTOR) {
            return $writeInfo;
        }

        return null;
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
        $scalarType = null;

        if (\count($originalPropertyTypes) === 1) {
            $propertyType = $originalPropertyTypes[0];
            $propertyBuiltInType = $propertyType->getBuiltinType();

            if (\in_array(
                $propertyBuiltInType,
                ['int', 'float', 'string', 'bool', 'null'],
                true,
            )) {
                $scalarType = $propertyBuiltInType;
            }
        }

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
}
