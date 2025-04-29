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

namespace Rekalogika\Mapper\Proxy\Implementation;

use Rekalogika\Mapper\Proxy\Metadata\ClassMetadata;
use Rekalogika\Mapper\Proxy\Metadata\PropertyMetadata;
use Rekalogika\Mapper\Proxy\ProxyMetadataFactoryInterface;

/**
 * @internal
 */
final class ProxyMetadataFactory implements ProxyMetadataFactoryInterface
{
    public function getMetadata(string $class): ClassMetadata
    {
        $properties = [];
        $reflectionClass = new \ReflectionClass($class);

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->name;
            $readOnly = $property->isReadOnly();

            if ($property->isPrivate()) {
                $scopeNotation = "\0$class\0$name";

                $properties[$scopeNotation] = new PropertyMetadata(
                    class: $class,
                    scopeClass: $class,
                    name: $name,
                    scopeNotation: $scopeNotation,
                    readOnly: $readOnly,
                );

                continue;
            }

            $properties[$name] = new PropertyMetadata(
                class: $class,
                scopeClass: $class,
                name: $name,
                scopeNotation: $name,
                readOnly: $readOnly,
            );

            if ($property->isProtected()) {
                $scopeNotation = "\0*\0$name";

                $properties[$scopeNotation] = new PropertyMetadata(
                    class: $class,
                    scopeClass: $class,
                    name: $name,
                    scopeNotation: $scopeNotation,
                    readOnly: $readOnly,
                );
            }

            while ($reflectionClass !== false) {
                $scopeClass = $reflectionClass->name;

                foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
                    if ($property->isStatic()) {
                        continue;
                    }

                    $name = $property->name;
                    $readOnly = $property->isReadOnly();
                    $scopeNotation = "\0$class\0$name";

                    $properties[$scopeNotation] = new PropertyMetadata(
                        class: $class,
                        scopeClass: $scopeClass,
                        name: $name,
                        scopeNotation: $scopeNotation,
                        readOnly: $readOnly,
                    );

                    $properties[$name] ??= new PropertyMetadata(
                        class: $class,
                        scopeClass: $scopeClass,
                        name: $name,
                        scopeNotation: $name,
                        readOnly: $readOnly,
                    );
                }

                $reflectionClass = $reflectionClass->getParentClass();
            }
        }

        return new ClassMetadata(
            class: $class,
            properties: array_values($properties),
        );
    }
}
