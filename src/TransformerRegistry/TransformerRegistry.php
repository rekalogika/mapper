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
use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
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
        $result = (function () use ($sourceTypes, $targetTypes) {
            foreach ($sourceTypes as $sourceType) {
                foreach ($targetTypes as $targetType) {
                    $transformers = $this->findBySourceAndTargetType(
                        $sourceType,
                        $targetType
                    );

                    foreach ($transformers as $transformer) {
                        yield new SearchResultEntry(
                            $sourceType,
                            $targetType,
                            $transformer
                        );
                    }
                }
            }
        })();

        return new SearchResult($result);
    }

    public function findBySourceAndTargetType(
        Type|MixedType $sourceType,
        Type|MixedType $targetType,
    ): iterable {
        $mapping = $this->getMappingBySourceAndTargetType($sourceType, $targetType);

        foreach ($mapping as $item) {
            $id = $item->getId();
            yield $id => $this->get($id);
        }
    }

    public function getMappingBySourceAndTargetType(
        Type|MixedType $sourceType,
        Type|MixedType $targetType,
    ): array {
        $sourceTypeStrings = $this->typeResolver
            ->getApplicableTypeStrings($sourceType);

        $targetTypeStrings = $this->typeResolver
            ->getApplicableTypeStrings($targetType);

        return $this->mappingFactory->getMapping()
            ->getMappingBySourceAndTarget($sourceTypeStrings, $targetTypeStrings);
    }
}
