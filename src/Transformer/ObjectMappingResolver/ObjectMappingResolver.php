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
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMapping;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMappingEntry;
use Rekalogika\Mapper\Transformer\ObjectMappingResolver\Contracts\ObjectMappingResolverInterface;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

final class ObjectMappingResolver implements ObjectMappingResolverInterface
{
    public function __construct(
        private PropertyAccessExtractorInterface $propertyAccessExtractor,
        private PropertyListExtractorInterface $propertyListExtractor,
    ) {
    }

    public function resolveObjectMapping(
        Type $sourceType,
        Type $targetType,
        Context $context
    ): ObjectMapping {
        $sourceProperties = $this->listSourceAttributes($sourceType, $context);
        $writableTargetProperties = $this
            ->listTargetWritableAttributes($targetType, $context);

        $propertiesToMap = array_intersect($sourceProperties, $writableTargetProperties);

        $results = [];

        foreach ($propertiesToMap as $property) {
            $results[] = new ObjectMappingEntry(
                $property,
                $property,
            );
        }

        return new ObjectMapping(
            $sourceType,
            $targetType,
            $results,
        );
    }

    /**
     * @return array<int,string>
     * @todo cache result
     */
    protected function listSourceAttributes(
        Type $sourceType,
        Context $context
    ): array {
        $class = $sourceType->getClassName();

        if (null === $class) {
            throw new InvalidArgumentException('Cannot get class name from source type.', context: $context);
        }

        $attributes = $this->propertyListExtractor->getProperties($class);

        if (null === $attributes) {
            throw new InvalidArgumentException(sprintf('Cannot get properties from source class "%s".', $class), context: $context);
        }

        $readableAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->propertyAccessExtractor->isReadable($class, $attribute)) {
                $readableAttributes[] = $attribute;
            }
        }

        return $readableAttributes;
    }

    /**
     * @return array<int,string>
     * @todo cache result
     */
    protected function listTargetWritableAttributes(
        Type $targetType,
        Context $context
    ): array {
        $class = $targetType->getClassName();

        if (null === $class) {
            throw new InvalidArgumentException('Cannot get class name from source type.', context: $context);
        }

        $attributes = $this->propertyListExtractor->getProperties($class);

        if (null === $attributes) {
            throw new InvalidArgumentException(sprintf('Cannot get properties from target class "%s".', $class), context: $context);
        }

        $writableAttributes = [];

        foreach ($attributes as $attribute) {
            if ($this->propertyAccessExtractor->isWritable($class, $attribute)) {
                $writableAttributes[] = $attribute;
            }
        }

        return $writableAttributes;
    }
}
