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

namespace Rekalogika\Mapper\Transformer\MetadataUtil;

use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;

/**
 * @internal
 */
final class PropertyAccessInfoExtractor
{
    /**
     * @var array<class-string,array<string,PropertyReadInfo|false>>
     */
    private array $readInfoCache = [];

    /**
     * @var array<class-string,array<string,PropertyWriteInfo|false>>
     */
    private array $writeInfoCache = [];

    /**
     * @var array<class-string,array<string,PropertyWriteInfo|false>>
     */
    private array $constructorInfoCache = [];

    public function __construct(
        private PropertyReadInfoExtractorInterface $propertyReadInfoExtractor,
        private PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor,
    ) {}

    /**
     * @param class-string $class
     */
    public function getReadInfo(
        string $class,
        string $property,
    ): ?PropertyReadInfo {
        $readInfo = $this->readInfoCache[$class][$property] ?? null;

        if ($readInfo !== null) {
            return $readInfo === false ? null : $readInfo;
        }

        $readInfo = $this->propertyReadInfoExtractor
            ->getReadInfo($class, $property);

        $this->readInfoCache[$class][$property] = $readInfo ?? false;

        return $readInfo;
    }

    /**
     * @param class-string $class
     */
    public function getWriteInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo {
        $writeInfo = $this->writeInfoCache[$class][$property] ?? null;

        if ($writeInfo !== null) {
            return $writeInfo === false ? null : $writeInfo;
        }

        $writeInfo = $this->propertyWriteInfoExtractor->getWriteInfo(
            class: $class,
            property: $property,
            context: [
                'enable_constructor_extraction' => false,
            ],
        );

        $this->writeInfoCache[$class][$property] = $writeInfo ?? false;

        return $writeInfo;
    }

    /**
     * @param class-string $class
     */
    public function getConstructorInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo {
        $constructorInfo = $this->constructorInfoCache[$class][$property] ?? null;

        if ($constructorInfo !== null) {
            return $constructorInfo === false ? null : $constructorInfo;
        }

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
            $this->constructorInfoCache[$class][$property] = false;

            return null;
        }

        $this->constructorInfoCache[$class][$property] = $constructorInfo;

        return $constructorInfo;
    }
}
