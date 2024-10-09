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

use Rekalogika\Mapper\Transformer\MetadataUtil\Model\Attributes;

/**
 * @internal
 */
interface AttributesExtractorInterface
{
    /**
     * @param class-string $class
     */
    public function getClassAttributes(string $class): Attributes;

    /**
     * @param class-string $class
     */
    public function getPropertyAttributes(string $class, string $property): Attributes;
}
