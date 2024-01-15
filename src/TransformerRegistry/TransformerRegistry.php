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

    public function findBySourceAndTargetTypes(
        iterable $sourceTypes,
        iterable $targetTypes,
    ): SearchResult {
        /** @var array<int,array{0:Type|MixedType,1:Type|MixedType,2:MappingEntry}> */
        $mappingEntries = [];

        foreach ($sourceTypes as $sourceType) {
            foreach ($targetTypes as $targetType) {
                $mapping = $this->getMappingBySourceAndTargetType(
                    $sourceType,
                    $targetType
                );

                foreach ($mapping as $mappingEntry) {
                    if ($mappingEntry->isVariantTargetType() || !TypeCheck::isObject($targetType)) {
                        $mappingEntries[] = [
                            $sourceType,
                            $targetType,
                            $mappingEntry,
                        ];
                    } else {
                        if (
                            TypeCheck::isSomewhatIdentical(
                                $targetType,
                                $mappingEntry->getTargetType()
                            )
                        ) {
                            $mappingEntries[] = [
                                $sourceType,
                                $targetType,
                                $mappingEntry,
                            ];
                        }
                    }
                }
            }
        }

        usort(
            $mappingEntries,
            fn (array $a, array $b)
            =>
            /** @psalm-suppress MixedMethodCall */
            $a[2]->getOrder() <=> $b[2]->getOrder()
        );

        $result = [];

        foreach ($mappingEntries as $mappingEntry) {
            $result[]  = new SearchResultEntry(
                $mappingEntry[2]->getOrder(),
                $mappingEntry[0],
                $mappingEntry[1],
                $this->get($mappingEntry[2]->getId()),
                $mappingEntry[2]->getId(),
                $mappingEntry[2]->isVariantTargetType()
            );
        }

        return new SearchResult($result);
    }

    /**
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
}
