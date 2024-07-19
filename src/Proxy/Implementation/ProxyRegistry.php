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

/**
 * @internal
 */
final class ProxyRegistry implements ProxyRegistryInterface, ProxyAutoloaderInterface
{
    /** @var ?\Closure(string): void */
    private ?\Closure $autoloader = null;

    public function __construct(
        private string $proxyDirectory,
    ) {
        // ensure directory exists
        if (!is_dir($this->proxyDirectory)) {
            mkdir($this->proxyDirectory, 0755, true);
        }
    }

    #[\Override]
    public function registerProxy(string $class, string $sourceCode): void
    {
        $proxyFile = sprintf(
            '%s/%s',
            $this->proxyDirectory,
            self::getProxyFileName($class)
        );

        $sourceCode = sprintf(
            '<?php declare(strict_types=1);' . "\n\n" . '%s',
            $sourceCode
        );

        file_put_contents($proxyFile, $sourceCode);
    }

    #[\Override]
    public function registerAutoloader(): void
    {
        if (null !== $this->autoloader) {
            return;
        }

        $proxyDirectory = $this->proxyDirectory;

        $this->autoloader = static function (string $class) use ($proxyDirectory): void {
            $proxyFile = sprintf(
                '%s/%s',
                $proxyDirectory,
                self::getProxyFileName($class)
            );

            if (file_exists($proxyFile)) {
                require $proxyFile;
            }
        };

        spl_autoload_register($this->autoloader);
    }

    #[\Override]
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
