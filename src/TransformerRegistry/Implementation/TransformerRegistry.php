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

namespace Rekalogika\Mapper\TransformerRegistry\Implementation;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Mapping\MappingEntry;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\TransformerRegistry\SearchResult;
use Rekalogika\Mapper\TransformerRegistry\SearchResultEntry;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class TransformerRegistry implements TransformerRegistryInterface
{
    public function __construct(
        private readonly ContainerInterface $transformersLocator,
        private readonly TypeResolverInterface $typeResolver,
        private readonly MappingFactoryInterface $mappingFactory,
    ) {
    }

    /**
     * @var array<string,TransformerInterface>
     */
    private array $transformers = [];

    public function get(string $id): TransformerInterface
    {
        if (isset($this->transformers[$id])) {
            return $this->transformers[$id];
        }

        $transformer = $this->transformersLocator->get($id);

        if (!$transformer instanceof TransformerInterface) {
            throw new LogicException(sprintf(
                'Transformer with id "%s" must implement %s',
                $id,
                TransformerInterface::class
            ));
        }

        return $this->transformers[$id] = $transformer;
    }

    /**
     * @todo cache this
     * @param Type|MixedType $sourceType
     * @param Type|MixedType $targetType
     * @return array<int,MappingEntry>
     */
    private function getMappingBySourceAndTargetType(
        Type|MixedType $sourceType,
        Type|MixedType $targetType,
    ): array {
        $sourceTypeStrings = $this->typeResolver
            ->getAcceptedTransformerInputTypeStrings($sourceType);

        $targetTypeStrings = $this->typeResolver
            ->getAcceptedTransformerOutputTypeStrings($targetType);

        return $this->mappingFactory->getMapping()
            ->getMappingBySourceAndTarget($sourceTypeStrings, $targetTypeStrings);
    }

    private function findBySourceAndTargetType(
        Type|MixedType $sourceType,
        Type|MixedType $targetType,
    ): SearchResult {
        $mapping = $this->getMappingBySourceAndTargetType(
            $sourceType,
            $targetType
        );

        $searchResultEntries = [];

        foreach ($mapping as $mappingEntry) {
            if ($mappingEntry->isVariantTargetType()) {
                // if variant

                $searchResultEntry = new SearchResultEntry(
                    mappingOrder: $mappingEntry->getOrder(),
                    sourceType: $sourceType,
                    targetType: $targetType,
                    transformerServiceId: $mappingEntry->getId(),
                    variantTargetType: $mappingEntry->isVariantTargetType()
                );

                $searchResultEntries[] = $searchResultEntry;
            } else {
                // if invariant, check if target type is somewhat identical

                if (
                    TypeCheck::isSomewhatIdentical(
                        $targetType,
                        $mappingEntry->getTargetType()
                    )
                ) {
                    $searchResultEntry = new SearchResultEntry(
                        mappingOrder: $mappingEntry->getOrder(),
                        sourceType: $sourceType,
                        targetType: $targetType,
                        transformerServiceId: $mappingEntry->getId(),
                        variantTargetType: $mappingEntry->isVariantTargetType()
                    );

                    $searchResultEntries[] = $searchResultEntry;
                }
            }
        }

        return new SearchResult($searchResultEntries);
    }

    public function findBySourceAndTargetTypes(
        array $sourceTypes,
        array $targetTypes,
    ): SearchResult {
        /** @var array<int,SearchResultEntry> */
        $searchResultEntries = [];

        foreach ($sourceTypes as $sourceType) {
            foreach ($targetTypes as $targetType) {
                $result = $this->findBySourceAndTargetType($sourceType, $targetType);
                foreach ($result as $searchResultEntry) {
                    $searchResultEntries[]
                        = $searchResultEntry;
                }
            }
        }

        usort($searchResultEntries, function (
            SearchResultEntry $a,
            SearchResultEntry $b
        ) {
            return $a->getMappingOrder() <=> $b->getMappingOrder();
        });

        return new SearchResult($searchResultEntries);
    }
}
