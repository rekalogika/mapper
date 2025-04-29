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

use Rekalogika\Mapper\CacheWarmer\WarmableProxyFactoryInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableProxyRegistryInterface;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Proxy\ProxyGeneratorInterface;
use Rekalogika\Mapper\Proxy\ProxyNamer;
use Rekalogika\Mapper\Proxy\ProxyRegistryInterface;

/**
 * @internal
 */
final readonly class WarmableVarExporterProxyFactory implements
    WarmableProxyFactoryInterface
{
    public function __construct(
        private ProxyRegistryInterface $proxyRegistry,
        private ProxyGeneratorInterface $proxyGenerator,
    ) {}

    /**
     * @param class-string $class
     */
    #[\Override]
    public function warmingCreateProxy(string $class): void
    {
        $targetProxyClass = ProxyNamer::generateProxyClassName($class);

        $sourceCode = $this->proxyGenerator
            ->generateProxyCode($class, $targetProxyClass);

        if (!class_exists($targetProxyClass, false)) {
            // @phpstan-ignore ekinoBannedCode.expression
            eval($sourceCode);
        }

        $proxyRegistry = $this->proxyRegistry;

        if (!$proxyRegistry instanceof  WarmableProxyRegistryInterface) {
            throw new LogicException(
                'The proxy registry must implement WarmingProxyRegistryInterface.',
            );
        }

        $proxyRegistry->warmingRegisterProxy($targetProxyClass, $sourceCode);
    }
}
