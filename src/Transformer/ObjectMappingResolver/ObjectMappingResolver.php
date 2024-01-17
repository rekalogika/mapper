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

namespace Rekalogika\Mapper\Transformer\ObjectMappingResolver;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ConstructorMapping;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMapping;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMappingResolverInterface;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\PropertyMapping;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

final class ObjectMappingResolver implements ObjectMappingResolverInterface
{
    public function __construct(
        private PropertyAccessExtractorInterface $propertyAccessExtractor,
        private PropertyListExtractorInterface $propertyListExtractor,
        private PropertyInitializableExtractorInterface $propertyInitializableExtractor,
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        private PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
    ) {
    }

    public function resolveObjectMapping(
        string $sourceClass,
        string $targetClass,
        Context $context
    ): ObjectMapping {

        // queries

        $readableSourceProperties = $this
            ->listReadableSourceProperties($sourceClass, $context);
        $writableTargetProperties = $this
            ->listTargetWritableProperties($targetClass, $context);
        $initializableTargetProperties = $this
            ->listTargetInitializableProperties($targetClass, $context);

        // process properties mapping

        $propertiesToMap = array_intersect($readableSourceProperties, $writableTargetProperties);

        $propertyResults = [];

        foreach ($propertiesToMap as $property) {
            $sourceProperty = $property;
            $targetProperty = $property;

            ///

            $targetPropertyTypes = $this->propertyTypeExtractor
                ->getTypes($targetClass, $targetProperty);

            if (null === $targetPropertyTypes || count($targetPropertyTypes) === 0) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot get type of target property "%s::$%s".',
                        $targetClass,
                        $targetProperty
                    ),
                    context: $context
                );
            }

            $sourcePropertyReadInfo = $this->propertyReadInfoExtractor
                ->getReadInfo($sourceClass, $sourceProperty);

            if (null === $sourcePropertyReadInfo) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot get read info of source property "%s::$%s".',
                        $sourceClass,
                        $sourceProperty
                    ),
                    context: $context
                );
            }

            $targetPropertyReadInfo = $this->propertyReadInfoExtractor
                ->getReadInfo($targetClass, $targetProperty);

            if (null === $targetPropertyReadInfo) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot get read info of target property "%s::$%s".',
                        $targetClass,
                        $targetProperty
                    ),
                    context: $context
                );
            }

            $targetPropertyWriteInfo = $this->propertyWriteInfoExtractor
                ->getWriteInfo($targetClass, $targetProperty);

            if (null === $targetPropertyWriteInfo) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot get write info of target property "%s::$%s".',
                        $targetClass,
                        $targetProperty
                    ),
                    context: $context
                );
            }

            $propertyResults[] = new PropertyMapping(
                sourceProperty: $sourceProperty,
                targetProperty: $targetProperty,
                targetTypes: $targetPropertyTypes,
                sourcePropertyReadInfo: $sourcePropertyReadInfo,
                targetPropertyReadInfo: $targetPropertyReadInfo,
                targetPropertyWriteInfo: $targetPropertyWriteInfo,
            );
        }

        // process source properties to target constructor mapping

        $initializableResults = [];

        foreach ($initializableTargetProperties as $property) {
            $sourceProperty = $property;
            $targetProperty = $property;

            ///

            if (!in_array($property, $readableSourceProperties)) {
                $sourceProperty = null;
            }

            $targetPropertyTypes = $this->propertyTypeExtractor
                ->getTypes($targetClass, $targetProperty);

            if (null === $targetPropertyTypes || count($targetPropertyTypes) === 0) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot get type of target property "%s::$%s".',
                        $targetClass,
                        $targetProperty
                    ),
                    context: $context
                );
            }

            $initializableResults[] = new ConstructorMapping(
                $sourceProperty,
                $targetProperty,
                $targetPropertyTypes,
            );
        }

        return new ObjectMapping(
            $sourceClass,
            $targetClass,
            $propertyResults,
            $initializableResults,
        );
    }

    /**
     * @param class-string $class
     * @return array<int,string>
     */
    private function listReadableSourceProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

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
    private function listTargetWritableProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

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
    private function listTargetInitializableProperties(
        string $class,
        Context $context
    ): array {
        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        $initializableProperties = [];

        foreach ($properties as $property) {
            if ($this->propertyInitializableExtractor->isInitializable($class, $property)) {
                $initializableProperties[] = $property;
            }
        }

        return $initializableProperties;
    }
}
