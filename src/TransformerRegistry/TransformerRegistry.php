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
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Util\TypeCheck;

class TransformerRegistry implements TransformerRegistryInterface
{
    public function __construct(
        private ContainerInterface $transformersLocator,
        private MappingFactoryInterface $mappingFactory,
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

    public function findBySourceAndTargetTypes(
        array $sourceTypes,
        array $targetTypes,
    ): SearchResult {
        $mapping = $this->mappingFactory->getMapping();

        /** @var array<int,SearchResultEntry> */
        $searchResultEntries = [];

        foreach ($mapping as $mappingEntry) {
            $mappingSourceType = $mappingEntry->getSourceType();
            $mappingTargetType = $mappingEntry->getTargetType();
            $mappingIsVariantTarget = $mappingEntry->isVariantTargetType();
            $mappingOrder = $mappingEntry->getOrder();

            if (isset($searchResultEntries[$mappingOrder])) {
                continue;
            }

            foreach ($sourceTypes as $sourceType) {
                if (
                    !TypeCheck::isTypeInstanceOf($sourceType, $mappingSourceType)
                ) {
                    continue;
                }

                foreach ($targetTypes as $targetType) {
                    if ($mappingIsVariantTarget) {
                        if (
                            TypeCheck::isTypeInstanceOf($targetType, $mappingTargetType)
                        ) {
                            $searchResultEntries[$mappingEntry->getOrder()] =
                                new SearchResultEntry(
                                    mappingOrder: $mappingEntry->getOrder(),
                                    sourceType: $sourceType,
                                    targetType: $targetType,
                                    transformerServiceId: $mappingEntry->getId(),
                                    variantTargetType: $mappingEntry->isVariantTargetType()
                                );
                        }
                    } else {
                        if (
                            TypeCheck::isSomewhatIdentical(
                                $targetType,
                                $mappingTargetType
                            )
                        ) {
                            $searchResultEntries[$mappingEntry->getOrder()] =
                                new SearchResultEntry(
                                    mappingOrder: $mappingEntry->getOrder(),
                                    sourceType: $sourceType,
                                    targetType: $targetType,
                                    transformerServiceId: $mappingEntry->getId(),
                                    variantTargetType: $mappingEntry->isVariantTargetType()
                                );
                        }
                    }
                }
            }
        }

        ksort($searchResultEntries);

        return new SearchResult($searchResultEntries);
    }
}
