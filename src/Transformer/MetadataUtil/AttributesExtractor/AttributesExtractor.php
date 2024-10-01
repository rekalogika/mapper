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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractor;

use Rekalogika\Mapper\Transformer\MetadataUtil\AttributesExtractorInterface;
use Rekalogika\Mapper\Transformer\MetadataUtil\Model\Attributes;
use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyAccessInfoExtractorInterface;
use Rekalogika\Mapper\Util\ClassUtil;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;

/**
 * @internal
 */
final readonly class AttributesExtractor implements AttributesExtractorInterface
{
    public function __construct(
        private PropertyAccessInfoExtractorInterface $propertyAccessInfoExtractor,
    ) {}

    public function getClassAttributes(string $class): Attributes
    {
        return new Attributes(ClassUtil::getClassAttributes($class, null));
    }

    public function getPropertyAttributes(string $class, string $property): Attributes
    {
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

        return new Attributes($attributes);
    }
}
