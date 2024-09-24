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
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final readonly class PropertyPathAwarePropertyTypeExtractor
{
    public function __construct(
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
    ) {}

    /**
     * @param class-string $class
     * @return list<Type>
     */
    public function getTypes(
        string $class,
        string $propertyPath,
    ): array {
        $propertyPathObject = new PropertyPath($propertyPath);

        /** @var \Iterator&PropertyPathIteratorInterface */
        $iterator = $propertyPathObject->getIterator();

        $currentPath = '';

        $currentClass = $class;

        $currentType = null;

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
                $currentPath .= '[' . $propertyPathPart . ']';
                $types = $currentType?->getCollectionValueTypes();
            } else {
                if ($currentClass === null) {
                    throw new PropertyPathAwarePropertyInfoExtractorException(
                        message: \sprintf('Trying to resolve path "%s", but the current node is not an object', $propertyPathPart),
                        class: $class,
                        propertyPath: $propertyPath,
                    );
                }

                $currentPath .= '.' . $propertyPathPart;
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

        return array_values($types ?? []);
    }
}
