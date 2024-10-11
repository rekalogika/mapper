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

namespace Rekalogika\Mapper\Transformer\MetadataUtil\TargetClassResolver;

use Rekalogika\Mapper\Transformer\MetadataUtil\TargetClassResolverInterface;

/**
 * @internal
 */
final class CachingTargetClassResolver implements TargetClassResolverInterface
{
    /**
     * @var array<class-string,array<class-string,class-string>>
     */
    private array $resolveTargetClassCache = [];

    /**
     * @var array<class-string,array<class-string,list<class-string>>>
     */
    private array $getAllConcreteTargetClassesCache = [];

    public function __construct(
        private readonly TargetClassResolverInterface $decorated,
    ) {}

    #[\Override]
    public function resolveTargetClass(
        string $sourceClass,
        string $targetClass,
    ): string {
        return $this->resolveTargetClassCache[$sourceClass][$targetClass]
            ??= $this->decorated->resolveTargetClass($sourceClass, $targetClass);
    }

    #[\Override]
    public function getAllConcreteTargetClasses(
        string $sourceClass,
        string $targetClass,
    ): array {
        return $this->getAllConcreteTargetClassesCache[$sourceClass][$targetClass]
            ??= $this->decorated->getAllConcreteTargetClasses($sourceClass, $targetClass);
    }
}
