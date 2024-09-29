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

namespace Rekalogika\Mapper\DependencyInjection;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\SubMapper\SubMapperInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;

/**
 * @internal
 */
final class ConfiguratorUtil
{
    /**
     * @return list<class-string>
     */
    private static function getExtraArgumentClasses(): array
    {
        return [
            Context::class,
            MainTransformerInterface::class,
            SubMapperInterface::class,
        ];
    }

    /**
     * @return list<string>
     */
    public static function getSourceClassesFromFirstArgument(
        \ReflectionMethod $method,
        ?\ReflectionParameter $parameter,
        ChildDefinition $definition,
    ): array {
        $sourceClasses = [];

        $type = $parameter?->getType();

        if ($type === null) {
            throw new LogicException(\sprintf(
                'Cannot set up property mapper, the type of the first argument cannot be determined. Service ID "%s", method "%s".',
                $definition->getClass() ?? 'unknown',
                $method->getName(),
            ));
        } elseif ($type instanceof \ReflectionNamedType) {
            if ($type->isBuiltin()) {
                throw new LogicException(\sprintf(
                    'Cannot set up object mapper, the first argument must take an object. Service ID "%s", method "%s".',
                    $definition->getClass() ?? '?',
                    $method->getName(),
                ));
            }

            $sourceClasses = [$type->getName()];
        } elseif ($type instanceof \ReflectionUnionType) {
            $sourceClasses = [];

            foreach ($type->getTypes() as $type) {
                if ($type instanceof \ReflectionIntersectionType) {
                    throw new LogicException(\sprintf(
                        'Cannot set up property mapper, the type of the first argument contains an intersection type, which is not supported. Service ID "%s", method "%s".',
                        $definition->getClass() ?? '?',
                        $method->getName(),
                    ));
                } elseif (!$type instanceof \ReflectionNamedType) {
                    throw new LogicException(\sprintf(
                        'Cannot set up property mapper, the type of the first argument contains a non-named type, which is not supported. Service ID "%s", method "%s".',
                        $definition->getClass() ?? '?',
                        $method->getName(),
                    ));
                }

                if ($type->isBuiltin()) {
                    throw new LogicException(\sprintf(
                        'Cannot set up object mapper, the first argument must take an object. Service ID "%s", method "%s".',
                        $definition->getClass() ?? '?',
                        $method->getName(),
                    ));
                }

                $sourceClasses[] = $type->getName();
            }
        } else {
            throw new LogicException(\sprintf(
                'Cannot set up property mapper, the type of the first argument is unsupported. Service ID "%s", method "%s".',
                $definition->getClass() ?? '?',
                $method->getName(),
            ));
        }

        return $sourceClasses;
    }

    /**
     * The method has the optional second parameter containing the existing
     * target value.
     *
     * @param class-string $class
     */
    public static function methodHasExistingTargetParameter(
        string $class,
        string $method,
    ): bool {
        $reflection = new \ReflectionMethod($class, $method);

        return self::hasExistingTargetParameter($reflection->getParameters()[1] ?? null);
    }

    public static function hasExistingTargetParameter(
        ?\ReflectionParameter $parameter,
    ): bool {
        if ($parameter === null) {
            return false;
        }

        $type = $parameter->getType();

        if ($type === null) {
            return true;
        }

        if (!$type instanceof \ReflectionNamedType) {
            return true;
        }


        if ($type->isBuiltin()) {
            return true;
        }

        $name = $type->getName();

        foreach (self::getExtraArgumentClasses() as $extraArgumentClass) {
            if ($name === $extraArgumentClass) {
                return false;
            }
        }

        return true;
    }

    public static function getReturnTypeClass(
        \ReflectionMethod $method,
        ChildDefinition $definition,
    ): string {
        $returnType = $method->getReturnType();

        if (
            $returnType === null
            || !$returnType instanceof \ReflectionNamedType
        ) {
            throw new LogicException(
                \sprintf(
                    'Unable to determine the target class for property mapper service "%s", method "%s".',
                    $definition->getClass() ?? '?',
                    $method->getName(),
                ),
            );
        }

        return $returnType->getName();
    }
}
