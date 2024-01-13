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

namespace Rekalogika\Mapper;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Contracts\MainTransformerInterface;
use Rekalogika\Mapper\Contracts\MixedType;
use Rekalogika\Mapper\Contracts\TransformerInterface;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\Exception\UnableToFindSuitableTransformerException;
use Rekalogika\Mapper\Mapping\MappingEntry;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\ObjectCache\ObjectCacheFactoryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Symfony\Component\PropertyInfo\Type;

class MainTransformer implements MainTransformerInterface
{
    public const OBJECT_CACHE = 'object_cache';

    public function __construct(
        private ContainerInterface $transformersLocator,
        private TypeResolverInterface $typeResolver,
        private MappingFactoryInterface $mappingFactory,
        private ObjectCacheFactoryInterface $objectCacheFactory,
    ) {
    }

    private function getTransformer(string $id): TransformerInterface
    {
        $transformer = $this->transformersLocator->get($id);

        if (!$transformer instanceof TransformerInterface) {
            throw new LogicException(sprintf(
                'Transformer with id "%s" must implement %s',
                $id,
                TransformerInterface::class
            ));
        }

        if ($transformer instanceof MainTransformerAwareInterface) {
            return $transformer->withMainTransformer($this);
        }
        return $transformer;
    }

    /**
     * @param array<string,mixed> $context
     */
    public static function getObjectCache(
        array &$context,
        ObjectCacheFactoryInterface $objectCacheFactory
    ): ObjectCache {
        if (!isset($context[self::OBJECT_CACHE])) {
            $objectCache = $objectCacheFactory->createObjectCache();
            $context[self::OBJECT_CACHE] = $objectCache;
        } else {
            /** @var mixed */
            $objectCache = $context[self::OBJECT_CACHE];

            if (!$objectCache instanceof ObjectCache) {
                throw new LogicException(sprintf(
                    'Object cache must be an instance of %s, %s given.',
                    ObjectCache::class,
                    get_debug_type($objectCache)
                ));
            }
        }

        return $objectCache;
    }

    public function transform(
        mixed $source,
        mixed $target,
        array $targetType,
        array $context
    ): mixed {
        // if targettype is not provided, guess it from target
        // if the target is also missing then the target is mixed

        if (count($targetType) === 0) {
            if ($target === null) {
                $targetType = [MixedType::instance()];
            } else {
                $targetType = [$this->typeResolver->guessTypeFromVariable($target)];
            }
        }

        // get object cache

        $objectCache = self::getObjectCache(
            $context,
            $this->objectCacheFactory
        );

        // gets simple target types from the provided target type

        $simpleTargetTypes = [];

        foreach ($targetType as $singleTargetType) {
            foreach ($this->typeResolver->getSimpleTypes($singleTargetType) as $simpleType) {
                $simpleTargetTypes[] = $simpleType;
            }
        }

        // guess the source type

        $sourceType = $this->typeResolver->guessTypeFromVariable($source);

        // iterate simple target types and find the suitable transformer

        foreach ($simpleTargetTypes as $singleTargetType) {
            $transformers = $this->getTransformers($sourceType, $singleTargetType);

            foreach ($transformers as $transformer) {
                /** @var mixed */
                $result = $transformer->transform(
                    source: $source,
                    target: $target,
                    sourceType: $sourceType,
                    targetType: $singleTargetType instanceof MixedType ? null : $singleTargetType,
                    context: $context
                );

                return $result;
            }
        }

        throw new UnableToFindSuitableTransformerException($sourceType, $targetType);
    }

    /**
     * @param Type|MixedType $sourceType
     * @param Type|MixedType $targetType
     * @return iterable<int,TransformerInterface>
     */
    private function getTransformers(
        Type|MixedType $sourceType,
        Type|MixedType $targetType,
    ): iterable {
        foreach ($this->getTransformerMapping($sourceType, $targetType) as $item) {
            $id = $item->getId();
            yield $this->getTransformer($id);
        }
    }

    /**
     * @param Type|MixedType $sourceType
     * @param Type|MixedType $targetType
     * @return array<int,MappingEntry>
     */
    public function getTransformerMapping(
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
