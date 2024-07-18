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

use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Proxy\ProxyGeneratorInterface;
use Rekalogika\Mapper\Proxy\ProxyNamer;
use Rekalogika\Mapper\Proxy\ProxyRegistryInterface;
use Rekalogika\Mapper\Util\ClassUtil;

/**
 * @internal
 */
final readonly class ProxyFactory implements ProxyFactoryInterface
{
    public function __construct(
        private ProxyRegistryInterface $proxyRegistry,
        private ProxyGeneratorInterface $proxyGenerator,
    ) {
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T):void $initializer
     * @return T
     */
    public function createProxy(
        string $class,
        $initializer,
        array $eagerProperties = []
    ): object {
        $targetProxyClass = ProxyNamer::generateProxyClassName($class);

        if (!class_exists($targetProxyClass)) {
            $sourceCode = $this->proxyGenerator
                ->generateProxyCode($class, $targetProxyClass);
            $this->proxyRegistry->registerProxy($targetProxyClass, $sourceCode);

            // @phpstan-ignore-next-line
            eval($sourceCode);

            // @phpstan-ignore-next-line
            if (!class_exists($targetProxyClass)) {
                throw new LogicException(
                    sprintf('Unable to find target proxy class "%s".', $targetProxyClass),
                );
            }
        }

        if (array_is_list($eagerProperties)) {
            $eagerProperties = array_filter($eagerProperties, fn ($property): bool => is_string($property));
            $eagerProperties = ClassUtil::getSkippedProperties($class, $eagerProperties);
        }

        /**
         * @psalm-suppress UndefinedMethod
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress MixedMethodCall
         * @var T
         */
        return $targetProxyClass::createLazyGhost($initializer, $eagerProperties);
    }
}
