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

namespace Rekalogika\Mapper\Mapping;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarExporter\VarExporter;

final class CachingMappingFactory implements
    MappingFactoryInterface,
    CacheWarmerInterface
{
    private ?Mapping $innerMappingCache = null;

    public function __construct(
        private MappingFactoryInterface $realMappingFactory,
        private KernelInterface $kernel,
    ) {
    }

    private function getMappingFromInnerFactory(): Mapping
    {
        if ($this->innerMappingCache === null) {
            $this->innerMappingCache = $this->realMappingFactory->getMapping();
        }

        return $this->innerMappingCache;
    }

    private function warmUpAndGetMapping(): Mapping
    {
        $this->warmUp($this->kernel->getCacheDir(), $this->kernel->getBuildDir());

        return $this->getMappingFromInnerFactory();
    }

    public function getMapping(): Mapping
    {
        if (!file_exists($this->getCacheFilePath())) {
            return $this->warmUpAndGetMapping();
        }

        try {
            /** @psalm-suppress UnresolvableInclude */
            $result = require $this->getCacheFilePath();
        } catch (\Throwable) {
            unlink($this->getCacheFilePath());

            return $this->warmUpAndGetMapping();
        }

        if (!$result instanceof Mapping) {
            unlink($this->getCacheFilePath());

            return $this->warmUpAndGetMapping();
        }

        return $result;
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function getCacheFilePath(): string
    {
        return $this->kernel->getBuildDir() . '/rekalogika_mapper_mapping.php';
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $mapping = VarExporter::export($this->realMappingFactory->getMapping());
        file_put_contents($this->getCacheFilePath(), '<?php return ' . $mapping . ';');

        return [];
    }
}
