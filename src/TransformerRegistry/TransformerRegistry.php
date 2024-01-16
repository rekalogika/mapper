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

namespace Rekalogika\Mapper\TransformerRegistry;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Mapping\MappingEntry;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\PropertyInfo\Type;

class TransformerRegistry implements TransformerRegistryInterface
{
    public function __construct(
        private ContainerInterface $transformersLocator,
        private TypeResolverInterface $typeResolver,
        private MappingFactoryInterface $mappingFactory,
    ) {
    }

    public function get(string $id): TransformerInterface
    {
        $transformer = $this->transformersLocator->get($id);

        if (!$transformer instanceof TransformerInterface) {
            throw new LogicException(sprintf(
                'Transformer with id "%s" must implement %s',
                $id,
                TransformerInterface::class
            ));
        }

        return $transformer;
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
        iterable $sourceTypes,
        iterable $targetTypes,
    ): SearchResult {
        /** @var array<int,SearchResultEntry> */
        $searchResultEntries = [];

        foreach ($sourceTypes as $sourceType) {
            foreach ($targetTypes as $targetType) {
                $result = $this->findBySourceAndTargetType($sourceType, $targetType);
                foreach ($result as $searchResultEntry) {
                    $searchResultEntries[] = $searchResultEntry;
                }
            }
        }

        usort(
            $searchResultEntries,
            fn (SearchResultEntry $a, SearchResultEntry $b)
            => $a->getMappingOrder() <=> $b->getMappingOrder()
        );

        return new SearchResult($searchResultEntries);
    }
}
