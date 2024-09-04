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

namespace Rekalogika\Mapper\CustomMapper;

use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;

/**
 * @internal
 */
interface PropertyMapperResolverInterface
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function getPropertyMapper(
        string $sourceClass,
        string $targetClass,
        string $property,
    ): ?ServiceMethodSpecification;
}
