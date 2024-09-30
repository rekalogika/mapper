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

use Rekalogika\Mapper\Transformer\MetadataUtil\Model\ClassMetadata;

/**
 * @internal
 */
interface ClassMetadataFactoryInterface
{
    /**
     * @param class-string $class
     * @todo collect property path attributes
     */
    public function createClassMetadata(
        string $class,
    ): ClassMetadata;
}
