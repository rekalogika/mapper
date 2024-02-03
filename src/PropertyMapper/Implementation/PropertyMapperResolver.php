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

namespace Rekalogika\Mapper\PropertyMapper\Implementation;

use Rekalogika\Mapper\PropertyMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\PropertyMapper\PropertyMapperServicePointer;

class PropertyMapperResolver implements PropertyMapperResolverInterface
{
    /**
     * @var array<class-string,array<string,array<class-string,PropertyMapperServicePointer>>>
     */
    private array $propertyMappers = [];

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @param array<int,PropertyMapperServicePointer::ARGUMENT_*> $extraArguments
     */
    public function addPropertyMapper(
        string $sourceClass,
        string $targetClass,
        string $property,
        string $serviceId,
        string $method,
        array $extraArguments = []
    ): void {
        $this->propertyMappers[$targetClass][$property][$sourceClass]
            = new PropertyMapperServicePointer($serviceId, $method, $extraArguments);
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     */
    public function getPropertyMapper(
        string $sourceClass,
        string $targetClass,
        string $property
    ): ?PropertyMapperServicePointer {
        if (!isset($this->propertyMappers[$targetClass][$property])) {
            return null;
        }

        $propertyMappers = $this->propertyMappers[$targetClass][$property];

        $sourceClassReflection = new \ReflectionClass($sourceClass);

        do {
            if (isset($propertyMappers[$sourceClassReflection->getName()])) {
                return $propertyMappers[$sourceClassReflection->getName()];
            }
        } while ($sourceClassReflection = $sourceClassReflection->getParentClass());

        foreach (class_implements($targetClass) as $interface) {
            if (isset($propertyMappers[$interface])) {
                return $propertyMappers[$interface];
            }
        }

        return null;
    }
}
