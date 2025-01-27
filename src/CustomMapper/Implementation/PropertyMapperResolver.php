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

namespace Rekalogika\Mapper\CustomMapper\Implementation;

use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 *
 * Not in hot path, no caching needed.
 */
final class PropertyMapperResolver implements PropertyMapperResolverInterface
{
    /**
     * @var array<class-string,array<string,array<class-string,ServiceMethodSpecification>>>
     */
    private array $propertyMappers = [];

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @param array<int,ServiceMethodSpecification::ARGUMENT_*> $extraArguments
     */
    public function addPropertyMapper(
        string $sourceClass,
        string $targetClass,
        string $property,
        string $serviceId,
        string $method,
        bool $hasExistingTarget,
        bool $ignoreUninitialized,
        array $extraArguments = [],
    ): void {
        $this->propertyMappers[$targetClass][$property][$sourceClass]
            = new ServiceMethodSpecification(
                serviceId: $serviceId,
                method: $method,
                hasExistingTarget: $hasExistingTarget,
                ignoreUninitialized: $ignoreUninitialized,
                extraArguments: $extraArguments,
            );
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    #[\Override]
    public function getPropertyMapper(
        string $sourceClass,
        string $targetClass,
        string $property,
    ): ?ServiceMethodSpecification {
        $sourceClasses = ClassUtil::getAllClassesFromObject($sourceClass);
        $targetClasses = ClassUtil::getAllClassesFromObject($targetClass);

        foreach ($sourceClasses as $sourceClass) {
            foreach ($targetClasses as $targetClass) {
                if (isset($this->propertyMappers[$targetClass][$property][$sourceClass])) {
                    return $this->propertyMappers[$targetClass][$property][$sourceClass];
                }
            }
        }

        return null;
    }
}
