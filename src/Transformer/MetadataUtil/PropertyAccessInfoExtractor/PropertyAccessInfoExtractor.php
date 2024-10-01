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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\PropertyAccessInfoExtractor;

use Rekalogika\Mapper\Transformer\MetadataUtil\PropertyAccessInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

/**
 * @internal
 */
final readonly class PropertyAccessInfoExtractor implements PropertyAccessInfoExtractorInterface
{
    public function __construct(
        private PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        private PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
    ) {}

    public function getReadInfo(
        string $class,
        string $property,
    ): ?PropertyReadInfo {
        return $this->propertyReadInfoExtractor
            ->getReadInfo($class, $property);
    }

    public function getWriteInfo(
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

    public function getConstructorInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo {
        $constructorInfo = $this->propertyWriteInfoExtractor->getWriteInfo(
            class: $class,
            property: $property,
            context: [
                'enable_constructor_extraction' => true,
                'enable_getter_setter_extraction' => false,
                'enable_magic_methods_extraction' => false,
                'enable_adder_remover_extraction' => false,
            ],
        );

        if (
            $constructorInfo === null
            || $constructorInfo->getType() !== PropertyWriteInfo::TYPE_CONSTRUCTOR
        ) {
            return null;
        }

        return $constructorInfo;
    }
}
