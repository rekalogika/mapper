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

use Rekalogika\Mapper\Transformer\Exception\PropertyPathAwarePropertyInfoExtractorException;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Model\PropertyPathMetadata;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class PropertyPathMetadataFactory
{
    public function __construct(
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
    ) {}

    /**
     * @param class-string $class
     */
    public function getMetadata(
        string $class,
        string $propertyPath,
    ): PropertyPathMetadata {
        $propertyPathObject = new PropertyPath($propertyPath);

        /** @var \Iterator&PropertyPathIteratorInterface */
        $iterator = $propertyPathObject->getIterator();

        $currentPath = '';

        $currentClass = $class;

        $currentType = null;

        $currentProperty = null;

        $lastClass = null;

        $lastIsIndex = false;

        /** @var list<Type>|null */
        $types = null;

        foreach ($iterator as $propertyPathPart) {
            \assert(\is_string($propertyPathPart));

            if ($types !== null) {
                if (\count($types) > 1) {
                    throw new PropertyPathAwarePropertyInfoExtractorException(
                        message: \sprintf('Cannot proceed because property "%s" has multiple types in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                        class: $class,
                        propertyPath: $propertyPath,
                    );
                }

                $currentType = $types[0];
                $currentClass = $currentType->getClassName();
            }

            if ($iterator->isIndex()) {
                $lastIsIndex = true;
                $currentPath .= '[' . $propertyPathPart . ']';
                $types = $currentType?->getCollectionValueTypes();
            } else {
                $lastIsIndex = false;
                if ($currentClass === null) {
                    throw new PropertyPathAwarePropertyInfoExtractorException(
                        message: \sprintf('Trying to resolve path "%s", but the current node is not an object', $propertyPathPart),
                        class: $class,
                        propertyPath: $propertyPath,
                    );
                }

                $currentPath .= '.' . $propertyPathPart;
                $currentProperty = $propertyPathPart;
                $lastClass = $currentClass;
                $types = $this->propertyTypeExtractor
                    ->getTypes($currentClass, $propertyPathPart);
            }

            if ($types === null) {
                throw new PropertyPathAwarePropertyInfoExtractorException(
                    message: \sprintf('Property "%s" not found in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                    class: $class,
                    propertyPath: $propertyPath,
                );
            } elseif (\count($types) === 0) {
                throw new PropertyPathAwarePropertyInfoExtractorException(
                    message: \sprintf('Cannot determine the type of property "%s" in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                    class: $class,
                    propertyPath: $propertyPath,
                );
            }
        }

        if ($lastClass === null) {
            throw new PropertyPathAwarePropertyInfoExtractorException(
                message: \sprintf('Property path "%s" is empty', $propertyPath),
                class: $class,
                propertyPath: $propertyPath,
            );
        }

        if (!class_exists($lastClass)) {
            throw new PropertyPathAwarePropertyInfoExtractorException(
                message: \sprintf('Class "%s" not found', $lastClass),
                class: $class,
                propertyPath: $propertyPath,
            );
        }

        if ($currentProperty !== null) {
            $attributes = ClassUtil::getPropertyAttributes(
                class: $lastClass,
                property: $currentProperty,
                attributeClass: null,
            );
        } else {
            $attributes = [];
        }

        $replaceable = $this->isReplaceable(
            class: $lastClass,
            property: $currentProperty,
            isIndex: $lastIsIndex,
        );

        return new PropertyPathMetadata(
            propertyPath: $propertyPath,
            class: $lastClass,
            property: $currentProperty,
            types: array_values($types ?? []),
            attributes: $attributes,
            replaceable: $replaceable,
        );
    }

    /**
     * @param class-string $class
     */
    private function isReplaceable(
        string $class,
        ?string $property,
        bool $isIndex,
    ): bool {
        if ($property === null) {
            return $isIndex;
        }

        $writeInfo = $this->propertyWriteInfoExtractor
            ->getWriteInfo($class, $property);

        if ($writeInfo === null) {
            return false;
        }

        return
            \in_array(
                $writeInfo->getType(),
                [PropertyWriteInfo::TYPE_METHOD, PropertyWriteInfo::TYPE_PROPERTY],
                true,
            )
            && $writeInfo->getVisibility() === PropertyWriteInfo::VISIBILITY_PUBLIC;
    }
}
