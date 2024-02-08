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

namespace Rekalogika\Mapper\Transformer\EagerPropertiesResolver\Implementation;

use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;

class HeuristicsEagerPropertiesResolver implements EagerPropertiesResolverInterface
{
    /**
     * @var array<int,string>
     */
    private $properties = [];

    /**
     * @param array<int,string>|null $properties
     */
    public function __construct(?array $properties = null)
    {
        $this->properties = $properties ?? [
            'id',
            'ID',
            'Id',
            'uuid',
            'UUID',
            'Uuid',
            'identifier'
        ];
    }

    public function getEagerProperties(string $sourceClass): array
    {
        $reflectionClass = new \ReflectionClass($sourceClass);

        foreach ($this->properties as $property) {
            try {
                $id = $reflectionClass->getProperty($property);
                if ($id->isPublic()) {
                    return [$property];
                }
            } catch (\ReflectionException) {
            }

            try {
                $methodName = 'get' . ucfirst($property);
                $id = $reflectionClass->getMethod($methodName);
                if ($id->isPublic()) {
                    return [$property];
                }
            } catch (\ReflectionException) {
            }
        }

        return [];
    }
}
