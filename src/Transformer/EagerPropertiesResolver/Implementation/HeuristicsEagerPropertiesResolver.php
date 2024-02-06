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
    public function getEagerProperties(string $sourceClass): array
    {
        $reflectionClass = new \ReflectionClass($sourceClass);

        try {
            $id = $reflectionClass->getProperty('id');
            if ($id->isPublic()) {
                return ['id'];
            }
        } catch (\ReflectionException) {
        }

        try {
            $id = $reflectionClass->getProperty('uuid');
            if ($id->isPublic()) {
                return ['uuid'];
            }
        } catch (\ReflectionException) {
        }

        try {
            $id = $reflectionClass->getMethod('getId');
            if ($id->isPublic()) {
                return ['id'];
            }
        } catch (\ReflectionException) {
        }

        try {
            $id = $reflectionClass->getMethod('getUuid');
            if ($id->isPublic()) {
                return ['uuid'];
            }
        } catch (\ReflectionException) {
        }

        return [];
    }
}
