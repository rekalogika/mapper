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

namespace Rekalogika\Mapper\CacheWarmer;

use Rekalogika\Mapper\MapperInterface;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final readonly class MapperCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private string $configDir,
        private MapperInterface $mapper,
    ) {}

    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * @return iterable<array{class-string,class-string}>
     */
    private function getObjectMapping(): iterable
    {
        $mappingCollection = new MappingCollection();

        $finder = new Finder();

        try {
            $files = $finder->files()->in($this->configDir)->name('*.php');
        } catch (DirectoryNotFoundException) {
            return [];
        }

        foreach ($files as $file) {
            $realPath = $file->getRealPath();

            if (false === $realPath || !file_exists($realPath)) {
                throw new \RuntimeException(\sprintf('The file "%s" does not exist.', $file->getRealPath()));
            }

            /** @psalm-suppress UnresolvableInclude */
            $callable = require $realPath;

            if (!\is_callable($callable)) {
                throw new \RuntimeException(\sprintf('The file "%s" must return a callable.', $file->getRealPath()));
            }

            $callable($mappingCollection);
        }

        return $mappingCollection->getClassMappings();
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $mapper = $this->mapper;

        if (!$mapper instanceof WarmableMapperInterface) {
            return [];
        }

        foreach ($this->getObjectMapping() as [$source, $target]) {
            $mapper->warmingMap($source, $target);
        }

        return [];
    }
}
