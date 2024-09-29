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

use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;

/**
 * @internal
 */
final class AttributesExtractor
{
    /**
     * @var array<class-string,list<object>>
     */
    private array $classAttributesCache = [];

    /**
     * @var array<class-string,array<string,list<object>>>
     */
    private array $propertyAttributesCache = [];

    public function __construct(
        private PropertyAccessInfoExtractor $propertyAccessInfoExtractor,
    ) {}

    /**
     * @param class-string $class
     * @return list<object>
     */
    public function getClassAttributes(string $class): array
    {
        $attributes = $this->classAttributesCache[$class] ?? null;

        if ($attributes !== null) {
            return $attributes;
        }

        $attributes = ClassUtil::getClassAttributes($class, null);

        return $this->classAttributesCache[$class] = $attributes;
    }

    /**
     * @param class-string $class
     * @param string $property
     * @return list<object>
     */
    public function getPropertyAttributes(string $class, string $property): array
    {
        $attributes = $this->propertyAttributesCache[$class][$property] ?? null;

        if ($attributes !== null) {
            return $attributes;
        }

        $readInfo = $this->propertyAccessInfoExtractor
            ->getReadInfo($class, $property);

        $writeInfo = $this->propertyAccessInfoExtractor
            ->getWriteInfo($class, $property);

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

        $attributes = ClassUtil::getPropertyAttributes(
            class: $class,
            property: $property,
            attributeClass: null,
            methods: $methods,
        );

        return $this->propertyAttributesCache[$class][$property] = $attributes;
    }
}
