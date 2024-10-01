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
use Symfony\Component\PropertyInfo\PropertyWriteInfo;

/**
 * @internal
 */
interface PropertyAccessInfoExtractorInterface
{
    /**
     * @param class-string $class
     */
    public function getReadInfo(
        string $class,
        string $property,
    ): ?PropertyReadInfo;

    /**
     * @param class-string $class
     */
    public function getWriteInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo;

    /**
     * @param class-string $class
     */
    public function getConstructorInfo(
        string $class,
        string $property,
    ): ?PropertyWriteInfo;
}
