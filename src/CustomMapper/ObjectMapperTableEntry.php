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
final readonly class ObjectMapperTableEntry
{
    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function __construct(
        private string $sourceClass,
        private string $targetClass,
        private ServiceMethodSpecification $serviceMethodSpecification
    ) {
    }

    public function getSourceClass(): string
    {
        return $this->sourceClass;
    }

    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    public function getServiceMethodSpecification(): ServiceMethodSpecification
    {
        return $this->serviceMethodSpecification;
    }
}
