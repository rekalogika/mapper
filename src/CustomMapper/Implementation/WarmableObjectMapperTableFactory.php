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

namespace Rekalogika\Mapper\CustomMapper\Implementation;

use Rekalogika\Mapper\CustomMapper\ObjectMapperTable;
use Rekalogika\Mapper\CustomMapper\ObjectMapperTableFactoryInterface;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarExporter\VarExporter;

/**
 * @internal
 */
final class WarmableObjectMapperTableFactory implements
    ObjectMapperTableFactoryInterface,
    CacheWarmerInterface
{
    public const CACHE_FILE = 'rekalogika_mapper_mapper_table.php';

    private ?ObjectMapperTable $objectMapperTableCache = null;

    public function __construct(
        private readonly ObjectMapperTableFactoryInterface $decorated,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function createObjectMapperTable(): ObjectMapperTable
    {
        if ($this->objectMapperTableCache !== null) {
            return $this->objectMapperTableCache;
        }

        try {
            $file = $this->getCacheFilePath();

            if (!file_exists($file)) {
                throw new UnexpectedValueException();
            }

            /** @psalm-suppress UnresolvableInclude */
            $result = require $file;

            if (!$result instanceof ObjectMapperTable) {
                throw new UnexpectedValueException();
            }

            return $this->objectMapperTableCache = $result;
        } catch (\Throwable) {
            @unlink($this->getCacheFilePath());

            return $this->objectMapperTableCache = $this->warmUpAndGetResult();
        }
    }

    private function warmUpAndGetResult(): ObjectMapperTable
    {
        $this->warmUp($this->kernel->getCacheDir(), $this->kernel->getBuildDir());

        return $this->decorated->createObjectMapperTable();
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $mapping = VarExporter::export($this->decorated->createObjectMapperTable());
        file_put_contents($this->getCacheFilePath(), '<?php return ' . $mapping . ';');

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function getCacheFilePath(): string
    {
        return $this->kernel->getBuildDir() . '/' . self::CACHE_FILE;
    }
}
