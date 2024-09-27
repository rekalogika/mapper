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

use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\Model\PropertyMetadata;

/**
 * @internal
 */
interface PropertyMetadataFactoryInterface
{
    /**
     * @param class-string $class
     * @todo collect property path attributes
     */
    public function createPropertyMetadata(
        string $class,
        string $property,
        bool $allowsDynamicProperties,
    ): PropertyMetadata;
}
