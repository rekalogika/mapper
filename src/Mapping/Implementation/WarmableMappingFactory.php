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

namespace Rekalogika\Mapper\Mapping\Implementation;

use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Mapping\Mapping;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarExporter\VarExporter;

final class WarmableMappingFactory implements MappingFactoryInterface
{
    public const CACHE_FILE = 'rekalogika_mapper_mapping.php';

    private ?Mapping $mapping = null;

    public function __construct(
        private MappingFactoryInterface $realMappingFactory,
        private KernelInterface $kernel,
    ) {
    }

    private function getMappingFromInnerFactory(): Mapping
    {
        return $this->realMappingFactory->getMapping();
    }

    private function warmUpAndGetMapping(): Mapping
    {
        $this->warmUp();

        return $this->getMappingFromInnerFactory();
    }

    public function getMapping(): Mapping
    {
        if ($this->mapping !== null) {
            return $this->mapping;
        }

        try {
            $file = $this->getCacheFilePath();

            if (!file_exists($file)) {
                throw new UnexpectedValueException();
            }

            /** @psalm-suppress UnresolvableInclude */
            $result = require $file;

            if (!$result instanceof Mapping) {
                throw new UnexpectedValueException();
            }
        } catch (\Throwable) {
            @unlink($this->getCacheFilePath());

            return $this->mapping = $this->warmUpAndGetMapping();
        }


        return $this->mapping = $result;
    }

    private function getCacheFilePath(): string
    {
        return $this->kernel->getBuildDir() . '/' . self::CACHE_FILE;
    }

    public function warmUp(): void
    {
        $mapping = VarExporter::export($this->realMappingFactory->getMapping());
        file_put_contents($this->getCacheFilePath(), '<?php return ' . $mapping . ';');
    }
}
