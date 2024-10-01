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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\UnalterableDeterminer;

use Rekalogika\Mapper\Attribute\Unalterable;
use Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\DynamicPropertiesDeterminerInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyAccessInfoExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\UnalterableDeterminerInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class UnalterableDeterminer implements UnalterableDeterminerInterface
{
    private const STATUS_PENDING = 1;
    private const STATUS_YES = 2;
    private const STATUS_NO = 3;

    /**
     * @var array<string,self::STATUS_*>
     */
    private array $cache = [];

    public function __construct(
        private readonly PropertyListExtractorInterface $propertyListExtractor,
        private readonly PropertyAccessInfoExtractorInterface $propertyAccessInfoExtractor,
        private readonly DynamicPropertiesDeterminerInterface $dynamicPropertiesDeterminer,
        private readonly AttributesExtractorInterface $attributesExtractor,
        private readonly PropertyTypeExtractorInterface $propertyTypeExtractor,
    ) {}

    /**
     * @param class-string $class
     */
    public function isClassUnalterable(string $class): bool
    {
        $status = $this->cache[$class] ?? null;

        if ($status === self::STATUS_YES) {
            return true;
        } elseif ($status === self::STATUS_NO) {
            return false;
        } elseif ($status === self::STATUS_PENDING) {
            $this->cache[$class] = self::STATUS_YES;

            return true;
        }

        $this->cache[$class] = self::STATUS_PENDING;
        $result = $this->realIsUnalterable($class);
        $this->cache[$class] = $result ? self::STATUS_YES : self::STATUS_NO;

        return $result;
    }

    /**
     * @param class-string $class
     */
    private function realIsUnalterable(string $class): bool
    {
        $reflectionClass = new \ReflectionClass($class);

        // common unalterable value object classes

        if (
            is_a($class, \DateTimeImmutable::class, true)
            || is_a($class, \DateInterval::class, true)
            || is_a($class, \DateTimeZone::class, true)
            || is_a($class, \DatePeriod::class, true)
            || is_a($class, \UnitEnum::class, true)
        ) {
            return true;
        }

        // common not unalterable value object classes

        if (
            is_a($class, \ArrayAccess::class, true)
        ) {
            return false;
        }

        // if allows dynamic properties, then it is not an unalterable value object

        if ($this->dynamicPropertiesDeterminer->allowsDynamicProperties($class)) {
            return false;
        }

        // if has magic __set() method, then it is not an unalterable value object

        if ($reflectionClass->hasMethod('__set')) {
            return false;
        }

        // if tagged by Unalterable attribute, then it is an unalterable value object

        $attributes = $this->attributesExtractor->getClassAttributes($class);
        $unalterableAttribute = $attributes->get(Unalterable::class);

        if ($unalterableAttribute !== null) {
            return $unalterableAttribute->isUnalterable;
        }

        // gets the list of properties

        $properties = $this->propertyListExtractor->getProperties($class) ?? [];

        // if any of the property is writable, then it is not an unalterable value object

        foreach ($properties as $property) {
            if ($this->isPropertyWritable($class, $property)) {
                return false;
            }
        }

        // if a property is readable and the type is not an unalterable value object, then it
        // is not an unalterable value object

        foreach ($properties as $property) {
            if (
                $this->isPropertyReadable($class, $property)
                && !$this->isPropertyTypeUnalterable($class, $property)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param class-string $class
     */
    private function isPropertyWritable(string $class, string $property): bool
    {
        $writeInfo = $this->propertyAccessInfoExtractor
            ->getWriteInfo($class, $property);

        return $writeInfo !== null
            && $writeInfo->getType() !== PropertyWriteInfo::TYPE_NONE;
    }

    /**
     * @param class-string $class
     */
    private function isPropertyReadable(string $class, string $property): bool
    {
        $readInfo = $this->propertyAccessInfoExtractor
            ->getReadInfo($class, $property);

        return $readInfo !== null;
    }

    private function isPropertyTypeUnalterable(string $class, string $property): bool
    {
        $types = $this->propertyTypeExtractor->getTypes($class, $property) ?? [];
        $types = array_values($types);

        if ($types === []) {
            return false;
        }

        return $this->isTypesUnalterable($types);
    }

    /**
     * @param list<Type> $types
     */
    public function isTypesUnalterable(array $types): bool
    {
        // not unalterable value object if any of the property type is not a
        // unalterable value object

        foreach ($types as $type) {
            $builtInType = $type->getBuiltinType();

            // if not an object, then it is an unalterable value object, we
            // cannot change it if we only have a read access to the variable

            if ($builtInType !== Type::BUILTIN_TYPE_OBJECT) {
                continue;
            }

            $class = $type->getClassName();

            // if class is not known, then we consider it not an unalterable
            // value object

            if ($class === null) {
                return false;
            }

            // if the class is invalid, then we consider it not an unalterable
            // value object

            if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
                return false;
            }

            // check the class if it is an unalterable value object

            if (!$this->isClassUnalterable($class)) {
                return false;
            }
        }

        return true;
    }
}
