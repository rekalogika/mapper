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

use Psr\Log\LoggerInterface;
use Rekalogika\Mapper\Exception\InvalidClassException;
use Rekalogika\Mapper\Mapping\Mapping;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\Transformer\AbstractTransformerDecorator;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
final class MappingFactory implements MappingFactoryInterface
{
    private ?Mapping $mapping = null;

    /**
     * @param iterable<string,TransformerInterface> $transformers
     */
    public function __construct(
        private iterable $transformers,
        private TypeResolverInterface $typeResolver,
        private ?LoggerInterface $logger = null,
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
        try {
            $supportedTransformation = $transformer->getSupportedTransformation();
        } catch (InvalidClassException $e) {
            // if we get invalid class exception here, we ignore the transformer

            $this->logger?->warning(
                'Transformer "{transformer}" has a mapping involving an invalid class "{class}", ignoring the transformer.',
                [
                    'transformer' => get_class($transformer),
                    'class' => $e->getClass(),
                ],
            );

            return;
        }

        // convert to iterator, so that we can catch an exception and able to
        // continue the iteration

        if (is_array($supportedTransformation)) {
            $supportedTransformation = new \ArrayIterator($supportedTransformation);
        } else {
            $supportedTransformation = new \IteratorIterator($supportedTransformation);
        }

        try {
            $supportedTransformation->rewind();
        } catch (InvalidClassException $e) {
            $this->logger?->warning(
                'Transformer "{transformer}" has a mapping involving an invalid class "{class}", ignoring the invalid mapping.',
                [
                    'transformer' => get_class($transformer),
                    'class' => $e->getClass(),
                ],
            );

            // if the error happens here, we ignore
        }

        while ($supportedTransformation->valid()) {
            try {
                $typeMapping = $supportedTransformation->current();
            } catch (InvalidClassException $e) {
                $this->logger?->warning(
                    'Transformer "{transformer}" has a mapping involving an invalid class "{class}", ignoring the invalid mapping.',
                    [
                        'transformer' => get_class($transformer),
                        'class' => $e->getClass(),
                    ],
                );

                // if the error happens here, we continue to the next mapping
                continue;
            }

            $sourceTypes = $this->getSimpleTypes($typeMapping->getSourceType());
            $targetTypes = $this->getSimpleTypes($typeMapping->getTargetType());
            $isVariantTargetType = $typeMapping->isVariantTargetType();

            if ($transformer instanceof AbstractTransformerDecorator) {
                $transformer = $transformer->getDecorated();
            }

            foreach ($sourceTypes as $sourceType) {
                foreach ($targetTypes as $targetType) {
                    $sourceTypeString = $this->typeResolver->getTypeString($sourceType);
                    $targetTypeString = $this->typeResolver->getTypeString($targetType);

                    $mapping->addEntry(
                        id: $id,
                        class: get_class($transformer),
                        sourceType: $sourceType,
                        targetType: $targetType,
                        sourceTypeString: $sourceTypeString,
                        targetTypeString: $targetTypeString,
                        variantTargetType: $isVariantTargetType,
                    );
                }
            }

            try {
                $supportedTransformation->next();
            } catch (InvalidClassException $e) {
                $this->logger?->warning(
                    'Transformer "{transformer}" has a mapping involving an invalid class "{class}", skipping mapping definition from this transformer.',
                    [
                        'transformer' => get_class($transformer),
                        'class' => $e->getClass(),
                    ],
                );

                // if the error happens here, we ignore
                continue;
            }
        }
    }

    /**
     * @return array<array-key,Type|MixedType>
     */
    private function getSimpleTypes(Type|MixedType $type): array
    {
        if ($type instanceof MixedType) {
            return [$type];
        }

        return $this->typeResolver->getSimpleTypes($type);
    }
}
