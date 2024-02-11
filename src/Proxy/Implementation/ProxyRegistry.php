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

use Rekalogika\Mapper\Proxy\ProxyAutoloaderInterface;
use Rekalogika\Mapper\Proxy\ProxyRegistryInterface;
use Rekalogika\Mapper\Proxy\ProxySpecification;

final class ProxyRegistry implements ProxyRegistryInterface, ProxyAutoloaderInterface
{
    /** @var ?callable(string): void */
    private $autoloader = null;

    public function __construct(
        private string $proxyDirectory,
    ) {
        // ensure directory exists
        if (!is_dir($this->proxyDirectory)) {
            mkdir($this->proxyDirectory, 0755, true);
        }
    }

    public function registerProxy(
        ProxySpecification $proxySpecification,
    ): void {
        $proxyClass = $proxySpecification->getClass();

        $proxyFile = sprintf(
            '%s/%s',
            $this->proxyDirectory,
            self::getProxyFileName($proxyClass)
        );

        file_put_contents($proxyFile, $proxySpecification->getCode());
    }

    public function registerAutoloader(): void
    {
        if (null !== $this->autoloader) {
            return;
        }

        $this->autoloader = function (string $class): void {
            $proxyFile = sprintf(
                '%s/%s',
                $this->proxyDirectory,
                self::getProxyFileName($class)
            );

            if (file_exists($proxyFile)) {
                require $proxyFile;
            }
        };

        spl_autoload_register($this->autoloader);
    }

    public function unregisterAutoloader(): void
    {
        if (null !== $this->autoloader) {
            spl_autoload_unregister($this->autoloader);
            $this->autoloader = null;
        }
    }


    private static function getProxyFileName(string $proxyClass): string
    {
        return sprintf('%s.php', hash('xxh128', $proxyClass));
    }
}
