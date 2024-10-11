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

/**
 * Resolves the target type hint to the actual class name. Especially useful
 * for resolving abstract classes or interfaces to concrete classes.
 *
 * @internal
 */
interface TargetClassResolverInterface
{
    /**
     * Resolves the target type hint to the actual class name. Especially useful
     * for resolving abstract classes or interfaces to concrete classes.
     *
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @return class-string
     */
    public function resolveTargetClass(
        string $sourceClass,
        string $targetClass,
    ): string;

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @return list<class-string>
     */
    public function getAllConcreteTargetClasses(
        string $sourceClass,
        string $targetClass,
    ): array;
}
