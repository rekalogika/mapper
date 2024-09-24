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

use Rekalogika\Mapper\Transformer\Exception\PropertyPathAwarePropertyTypeExtractorException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathIteratorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\TypeInfo\Type as TypeInfoType;

/**
 * @internal
 */
final readonly class PropertyPathAwarePropertyTypeExtractor implements PropertyTypeExtractorInterface
{
    public function __construct(
        private PropertyTypeExtractorInterface $decorated,
    ) {}

    /**
     * @param array<string,mixed> $context
     * @return TypeInfoType
     */
    public function getType(
        string $class,
        string $property,
        array $context = [],
    ) {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * @param array<array-key,mixed> $context
     * @return null|array<array-key,Type>
     */
    #[\Override]
    public function getTypes(
        string $class,
        string $property,
        array $context = [],
    ): ?array {
        if (!$this->isPropertyPath($property)) {
            return $this->decorated->getTypes($class, $property, $context);
        }

        $propertyPathObject = new PropertyPath($property);

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
                    throw new PropertyPathAwarePropertyTypeExtractorException(
                        message: \sprintf('Cannot proceed because property "%s" has multiple types in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                        class: $class,
                        propertyPath: $property,
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
                    throw new PropertyPathAwarePropertyTypeExtractorException(
                        message: \sprintf('Trying to resolve path "%s", but the current node is not an object', $propertyPathPart),
                        class: $class,
                        propertyPath: $property,
                    );
                }

                $currentPath .= '.' . $propertyPathPart;
                $types = $this->decorated
                    ->getTypes($currentClass, $propertyPathPart, $context);
            }

            if ($types === null) {
                throw new PropertyPathAwarePropertyTypeExtractorException(
                    message: \sprintf('Property "%s" not found in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                    class: $class,
                    propertyPath: $property,
                );
            } elseif (\count($types) === 0) {
                throw new PropertyPathAwarePropertyTypeExtractorException(
                    message: \sprintf('Cannot determine the type of property "%s" in class "%s"', $propertyPathPart, $currentClass ?? 'unknown'),
                    class: $class,
                    propertyPath: $property,
                );
            }
        }

        return array_values($types ?? []);
    }

    private function isPropertyPath(string $property): bool
    {
        return str_contains($property, '.') || str_contains($property, '[');
    }
}
