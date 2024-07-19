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

use Rekalogika\Mapper\Proxy\Exception\ProxyNotSupportedException;
use Rekalogika\Mapper\Proxy\ProxyGeneratorInterface;
use Symfony\Component\VarExporter\Exception\LogicException;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @internal
 */
final readonly class ProxyGenerator implements ProxyGeneratorInterface
{
    #[\Override]
    public function generateProxyCode(
        string $realClass,
        string $proxyClass
    ): string {
        try {
            $proxyCode = $this->generateProxySourceCode($realClass, $proxyClass);
        } catch (LogicException $e) {
            throw new ProxyNotSupportedException($realClass, previous: $e);
        }

        return $proxyCode;
    }

    /**
     * @param class-string $realClass
     */
    private function generateProxySourceCode(string $realClass, string $proxyClass): string
    {
        $targetReflection = new \ReflectionClass($realClass);

        // get proxy class name & namespace
        $shortName = preg_replace('/.*\\\\/', '', $proxyClass);
        $namespace = preg_replace('/\\\\[^\\\\]*$/', '', $proxyClass);

        return
            $this->getClassHeader() .
            sprintf('namespace %s;', $namespace) . "\n\n" .
            sprintf(
                'final %sclass %s%s',
                $targetReflection->isReadOnly() ? 'readonly ' : '',
                $shortName,
                ProxyHelper::generateLazyGhost($targetReflection)
            );
    }

    private function getClassHeader(): string
    {
        return <<<'PHP'
/*
 * This is a proxy class automatically generated by the rekalogika/mapper
 * package. Do not edit it manually.
 */


PHP;
    }
}
