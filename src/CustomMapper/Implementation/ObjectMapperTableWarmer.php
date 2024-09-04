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

use Rekalogika\Mapper\CustomMapper\ObjectMapperTableFactoryInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarExporter\VarExporter;

/**
 * @internal
 */
final readonly class ObjectMapperTableWarmer implements CacheWarmerInterface
{
    public function __construct(
        private ObjectMapperTableFactoryInterface $objectMapperTableFactory,
        private KernelInterface $kernel,
    ) {}

    #[\Override]
    public function isOptional(): bool
    {
        return false;
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $mapping = VarExporter::export($this->objectMapperTableFactory->createObjectMapperTable());
        file_put_contents($this->getCacheFilePath(), '<?php return '.$mapping.';');

        return [];
    }

    private function getCacheFilePath(): string
    {
        return $this->kernel->getBuildDir().'/rekalogika_mapper_mapper_table.php';
    }
}
