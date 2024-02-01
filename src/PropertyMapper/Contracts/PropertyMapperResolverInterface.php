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

namespace Rekalogika\Mapper\PropertyMapper\Contracts;

interface PropertyMapperResolverInterface
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function addPropertyMapper(
        string $sourceClass,
        string $targetClass,
        string $property,
        string $serviceId,
        string $method
    ): void;

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function getPropertyMapper(
        string $sourceClass,
        string $targetClass,
        string $property
    ): ?PropertyMapperServicePointer;
}