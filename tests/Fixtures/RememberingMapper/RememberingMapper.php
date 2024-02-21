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

namespace Rekalogika\Mapper\Tests\Fixtures\RememberingMapper;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\MapperInterface;
use Rekalogika\Mapper\ObjectCache\ObjectCacheFactoryInterface;
use Rekalogika\Mapper\Transformer\Context\PresetMapping;
use Rekalogika\Mapper\Transformer\Context\PresetMappingFactory;
use Symfony\Contracts\Service\ResetInterface;

class RememberingMapper implements MapperInterface, ResetInterface
{
    private PresetMapping $presetMapping;

    public function __construct(
        private MapperInterface $decorated,
        private ObjectCacheFactoryInterface $objectCacheFactory
    ) {
        $this->presetMapping = new PresetMapping();
    }

    public function reset(): void
    {
        $this->presetMapping = new PresetMapping();
    }

    /** @psalm-suppress InvalidReturnType */
    public function map(object $source, object|string $target, ?Context $context = null): object
    {
        $objectCache = $this->objectCacheFactory->createObjectCache();

        $context ??= Context::create();
        $context = $context->with($objectCache, $this->presetMapping);

        $result = $this->decorated->map($source, $target, $context);

        $newPresetMapping = PresetMappingFactory::fromObjectCacheReversed($objectCache);
        $this->presetMapping->mergeFrom($newPresetMapping);

        /** @psalm-suppress InvalidReturnStatement */
        return $result;
    }
}
