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

namespace Rekalogika\Mapper\Transformer\ObjectToObjectMetadata;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\PropertyMapper\Contracts\PropertyMapperResolverInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\PropertyMapping;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

final class ObjectToObjectMetadataFactory implements ObjectToObjectMetadataFactoryInterface
{
    public function __construct(
        private PropertyAccessExtractorInterface $propertyAccessExtractor,
        private PropertyListExtractorInterface $propertyListExtractor,
        private PropertyInitializableExtractorInterface $propertyInitializableExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyMapperResolverInterface $propertyMapperResolver
    ) {
    }

    public function createObjectToObjectMetadata(
        string $sourceClass,
        string $targetClass,
        Context $context
    ): ObjectToObjectMetadata {
        $objectToObjectMetadata = new ObjectToObjectMetadata($sourceClass, $targetClass);

        // queries

        $readableSourceProperties = $this
            ->listReadableProperties($sourceClass, $context);
        $readableTargetProperties = $this
            ->listReadableProperties($targetClass, $context);
        $writableTargetProperties = $this
            ->listWritableProperties($targetClass, $context);
        $initializableTargetProperties = $this
            ->listInitializableProperties($targetClass, $context);
        $targetProperties = $this
            ->listProperties($targetClass, $context);

        $initializableTargetPropertiesNotInSource = $initializableTargetProperties;

        // determine if targetClass is instantiable

        $reflectionClass = new \ReflectionClass($targetClass);
        $objectToObjectMetadata->setInstantiable($reflectionClass->isInstantiable());
        $objectToObjectMetadata->setCloneable($reflectionClass->isCloneable());

        // process properties mapping

        foreach ($targetProperties as $targetProperty) {
            $sourceProperty = $targetProperty;

            // determine if a property mapper is defined for the property
            $propertyMapperPointer = $this->propertyMapperResolver
                ->getPropertyMapper($sourceClass, $targetClass, $targetProperty);

            ///

            $isSourceReadable = in_array($sourceProperty, $readableSourceProperties);
            $isTargetReadable = in_array($targetProperty, $readableTargetProperties);
            $isTargetWritable = in_array($targetProperty, $writableTargetProperties);
            $isTargetInitializable = in_array($targetProperty, $initializableTargetProperties);

            // target is initializeble, remove the property from the list of
            // uninitialized properties
            if ($isTargetInitializable) {
                $initializableTargetPropertiesNotInSource = array_diff($initializableTargetPropertiesNotInSource, [$targetProperty]);
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
                sourceProperty: $isSourceReadable ? $sourceProperty : null,
                targetProperty: $targetProperty,
                sourceTypes: $sourcePropertyTypes,
                targetTypes: $targetPropertyTypes,
                readSource: $isSourceReadable,
                initializeTarget: $isTargetInitializable,
                writeTarget: $isTargetWritable,
                readTarget: $isTargetReadable,
                targetScalarType: $targetPropertyScalarType,
                propertyMapper: $propertyMapperPointer,
            );

            $objectToObjectMetadata->addPropertyMapping($propertyMapping);
        }

        $objectToObjectMetadata
            ->setInitializableTargetPropertiesNotInSource($initializableTargetPropertiesNotInSource);

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
    private function listReadableProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->listProperties($class, $context);

        $readableProperties = [];

        foreach ($properties as $property) {
            if ($this->propertyAccessExtractor->isReadable($class, $property)) {
                $readableProperties[] = $property;
            }
        }

        return $readableProperties;
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     */
    private function listWritableProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->listProperties($class, $context);

        $writableProperties = [];

        foreach ($properties as $property) {
            if ($this->propertyAccessExtractor->isWritable($class, $property)) {
                $writableProperties[] = $property;
            }
        }

        return $writableProperties;
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
