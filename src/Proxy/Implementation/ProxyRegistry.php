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

use Rekalogika\Mapper\CacheWarmer\WarmableProxyRegistryInterface;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Proxy\ProxyAutoloaderInterface;
use Rekalogika\Mapper\Proxy\ProxyRegistryInterface;

/**
 * @internal
 */
final class ProxyRegistry implements
    ProxyRegistryInterface,
    ProxyAutoloaderInterface,
    WarmableProxyRegistryInterface
{
    /** @var ?\Closure(string): void */
    private ?\Closure $autoloader = null;

    public function __construct(
        private readonly string $proxyDirectory,
        private readonly ?string $preWarmedProxyDirectory = null,
    ) {
        // ensure directory exists
        if (!is_dir($this->proxyDirectory)) {
            mkdir($this->proxyDirectory, 0755, true);
        }
    }

    #[\Override]
    public function warmingRegisterProxy(string $class, string $sourceCode): void
    {
        $preWarmedProxyDirectory = $this->preWarmedProxyDirectory;

        if (null === $preWarmedProxyDirectory) {
            throw new LogicException('Pre-warmed proxy directory is not set.');
        }

        if (!is_dir($preWarmedProxyDirectory)) {
            mkdir($preWarmedProxyDirectory, 0755, true);
        }

        $this->doRegisterProxy($class, $sourceCode, $preWarmedProxyDirectory);
    }

    #[\Override]
    public function registerProxy(string $class, string $sourceCode): void
    {
        $this->doRegisterProxy($class, $sourceCode, $this->proxyDirectory);
    }

    private function doRegisterProxy(
        string $class,
        string $sourceCode,
        string $directory,
    ): void {
        $proxyFile = \sprintf(
            '%s/%s',
            $directory,
            self::getProxyFileName($class),
        );

        $sourceCode = \sprintf(
            '<?php declare(strict_types=1);' . "\n\n" . '%s',
            $sourceCode,
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
        $preWarmedProxyDirectory = $this->preWarmedProxyDirectory;

        $this->autoloader = static function (string $class) use ($proxyDirectory, $preWarmedProxyDirectory): void {
            $proxyFileName = self::getProxyFileName($class);

            if ($preWarmedProxyDirectory !== null) {
                $preWarmedProxyFile = \sprintf(
                    '%s/%s',
                    $preWarmedProxyDirectory,
                    $proxyFileName,
                );

                if (file_exists($preWarmedProxyFile)) {
                    require $preWarmedProxyFile;

                    return;
                }
            }

            $proxyFile = \sprintf(
                '%s/%s',
                $proxyDirectory,
                $proxyFileName,
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
        return \sprintf('%s.php', hash('xxh128', $proxyClass));
    }
}
