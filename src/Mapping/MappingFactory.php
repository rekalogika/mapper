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

use Rekalogika\Mapper\Contracts\TransformerInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;

/**
 * Initialize transformer mappings
 */
final class MappingFactory implements MappingFactoryInterface
{
    private ?Mapping $mapping = null;

    /**
     * @param iterable<string,TransformerInterface> $transformers
     */
    public function __construct(
        private iterable $transformers,
        private TypeResolverInterface $typeResolver
    ) {
    }

    public function getMapping(): Mapping
    {
        if ($this->mapping === null) {
            $this->mapping = $this->createMapping($this->transformers);
        }

        return $this->mapping;
    }

    /**
     * @param iterable<string,TransformerInterface> $transformers
     * @return Mapping
     */
    private function createMapping(iterable $transformers): Mapping
    {
        $mapping = new Mapping();

        foreach ($transformers as $id => $transformer) {
            $this->addMapping($mapping, $id, $transformer);
        }

        return $mapping;
    }

    private function addMapping(
        Mapping $mapping,
        string $id,
        TransformerInterface $transformer
    ): void {
        foreach ($transformer->getSupportedTransformation() as $typeMapping) {
            $sourceTypes = $typeMapping->getSimpleSourceTypes();
            $targetTypes = $typeMapping->getSimpleTargetTypes();

            foreach ($sourceTypes as $sourceType) {
                foreach ($targetTypes as $targetType) {
                    $sourceTypeString = $this->typeResolver->getTypeString($sourceType);
                    $targetTypeString = $this->typeResolver->getTypeString($targetType);

                    $mapping->addEntry(
                        id: $id,
                        class: get_class($transformer),
                        sourceType: $sourceTypeString,
                        targetType: $targetTypeString
                    );
                }
            }
        }
    }
}
