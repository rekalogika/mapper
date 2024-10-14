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

namespace Rekalogika\Mapper\TransformerProcessor\PropertyProcessor;

use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\PropertyMapping;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorFactoryInterface;
use Rekalogika\Mapper\TransformerProcessor\PropertyProcessorInterface;

/**
 * @internal
 */
final class CachingPropertyProcessorFactory implements PropertyProcessorFactoryInterface
{
    use MainTransformerAwareTrait;

    /**
     * @var array<string,PropertyProcessorInterface> $cache
     */
    private array $cache = [];

    private ?PropertyProcessorFactoryInterface $decoratedWithMainTransformer = null;

    public function __construct(
        private readonly PropertyProcessorFactoryInterface $decorated,
    ) {}

    private function getDecorated(): PropertyProcessorFactoryInterface
    {
        return $this->decoratedWithMainTransformer ??= $this->decorated
            ->withMainTransformer($this->getMainTransformer());
    }

    public function getPropertyProcessor(
        PropertyMapping $metadata,
    ): PropertyProcessorInterface {
        $id = $metadata->getId();

        return $this->cache[$id] ??= $this->getDecorated()
            ->getPropertyProcessor($metadata);
    }
}
