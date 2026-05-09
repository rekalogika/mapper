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

namespace Rekalogika\Mapper\MainTransformer\Implementation;

use Rekalogika\Mapper\CacheWarmer\MappingCache;
use Rekalogika\Mapper\CacheWarmer\WarmableMainTransformerInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableTransformerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\MapperOptions;
use Rekalogika\Mapper\MainTransformer\Exception\CannotFindTransformerException;
use Rekalogika\Mapper\MainTransformer\Exception\CircularReferenceException;
use Rekalogika\Mapper\MainTransformer\Exception\TransformerReturnsUnexpectedValueException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\MainTransformer\Model\DebugContext;
use Rekalogika\Mapper\MainTransformer\Model\Path;
use Rekalogika\Mapper\ObjectCache\Exception\CircularReferenceException as ObjectCacheCircularReferenceException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\ObjectCache\ObjectCacheFactoryInterface;
use Rekalogika\Mapper\ObjectCache\Sentinel\CachedTargetObjectNotFoundSentinel;
use Rekalogika\Mapper\Transformer\Exception\RefuseToTransformException;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\TransformerRegistry\Implementation\CachingTransformerRegistry;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\TypeInfo\Type;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
final class MainTransformer implements
    MainTransformerInterface,
    ResetInterface,
    WarmableMainTransformerInterface
{
    public static int $manualGcInterval = 500;

    private static int $runCounter = 1;

    public function __construct(
        private readonly ObjectCacheFactoryInterface $objectCacheFactory,
        private readonly TransformerRegistryInterface $transformerRegistry,
        private readonly bool $debug = false,
    ) {}

    #[\Override]
    public function reset(): void
    {
        self::$runCounter = 1;
    }

    private function processTransformer(
        TransformerInterface $transformer,
    ): TransformerInterface {
        if ($transformer instanceof MainTransformerAwareInterface) {
            return $transformer->withMainTransformer($this);
        }

        return $transformer;
    }

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
        ?string $path = null,
    ): mixed {
        // if MapperOptions is not provided, use the default options

        if (($mapperOptions = $context(MapperOptions::class)) === null) {
            $mapperOptions = new MapperOptions();
            $context = $context->with($mapperOptions);
        }

        // if manual garbage collection interval is set, run it

        if (self::$manualGcInterval > 0) {
            if (self::$runCounter % self::$manualGcInterval === 0) {
                gc_collect_cycles();
            }

            self::$runCounter++;
        }

        // if target is provided, guess the type from it.
        // if target is not provided, use the provided target type. if it is
        // also not provided, then the target type is mixed.

        if ($target === null) {
            if ($targetType === null) {
                $targetType = Type::mixed();
            }
        } elseif ($targetType === null) {
            $targetType = TypeGuesser::guessTypeFromVariable($target);
        }

        // get or create object cache

        if (($objectCache = $context(ObjectCache::class)) === null) {
            $objectCache = $this->objectCacheFactory->createObjectCache();
            $context = $context->with($objectCache);
        }

        // initialize path it it doesn't exist

        if (($pathContext = $context(Path::class)) !== null) {
            // append path

            if ($path === null) {
                $path = '(not specified)';
            }

            $context = $context->with($pathContext->append($path));
        } else {
            $pathContext = Path::create();
            $context = $context->with($pathContext);
        }

        // determine the source type

        if ($sourceType !== null) {
            $sourceTypes = [$sourceType];
            $isSourceTypeGuessed = false;
        } else {
            $sourceTypes = [TypeGuesser::guessTypeFromVariable($source)];
            $isSourceTypeGuessed = true;
        }

        // search for the matching transformers according to the source and
        // target types

        $searchResult = $this->transformerRegistry
            ->findBySourceAndTargetTypes(
                $sourceTypes,
                [$targetType],
            );

        // if debug, inject debug context

        if ($this->debug) {
            $context = $context->with(
                new DebugContext(
                    sourceType: $sourceTypes[0],
                    targetTypes: $searchResult->getTargetTypes(),
                    sourceTypeGuessed: $isSourceTypeGuessed,
                ),
            );
        }

        // loop over the result and transform the source to the target

        foreach ($searchResult as $searchResultEntry) {
            // if the target exists, we make sure it is of the same type as
            // the target type of the search entry, if not continue to the next
            // search entry

            if ($target !== null && !TypeCheck::isVariableInstanceOf($target, $searchResultEntry->getTargetType())) {
                continue;
            }

            // TransformerInterface treats Type::mixed() as "no info" and
            // expects null in that case

            $entrySourceType = $searchResultEntry->getSourceType();
            $sourceTypeForTransformer = TypeCheck::isMixed($entrySourceType) ? null : $entrySourceType;

            $entryTargetType = $searchResultEntry->getTargetType();
            $targetTypeForTransformer = TypeCheck::isMixed($entryTargetType) ? null : $entryTargetType;

            // if the target type is cached, return it. otherwise, pre-cache it

            if ($targetTypeForTransformer !== null) {
                try {
                    $result = $objectCache
                        ->getTarget($source, $targetTypeForTransformer);
                } catch (ObjectCacheCircularReferenceException $e) {
                    throw new CircularReferenceException(
                        source: $source,
                        targetType: $targetTypeForTransformer,
                        context: $context,
                        previous: $e,
                    );
                }

                if (!$result instanceof CachedTargetObjectNotFoundSentinel) {
                    return $result;
                }

                $objectCache
                    ->preCache($source, $targetTypeForTransformer);
            }

            // get and prepare transformer
            $transformer = $this->processTransformer(
                $this->transformerRegistry->get(
                    $searchResultEntry->getTransformerServiceId(),
                ),
            );

            // transform the source to the target

            try {
                /** @var mixed */
                $result = $transformer->transform(
                    source: $source,
                    target: $target,
                    sourceType: $sourceTypeForTransformer,
                    targetType: $targetTypeForTransformer,
                    context: $context,
                );
            } catch (RefuseToTransformException) {
                if ($targetTypeForTransformer !== null) {
                    $objectCache->undoPreCache($source, $targetTypeForTransformer);
                }

                continue;
            }

            // check the result type

            if (
                !TypeCheck::isVariableInstanceOf($result, $entryTargetType)
            ) {
                throw new TransformerReturnsUnexpectedValueException($source, $entryTargetType, $result, $transformer, $context);
            }

            // if the target type is not null, cache it

            if ($targetTypeForTransformer !== null) {
                $objectCache->saveTarget(
                    source: $source,
                    targetType: $targetTypeForTransformer,
                    target: $result,
                    addIfAlreadyExists: true,
                );
            }

            return $result;
        }

        throw new CannotFindTransformerException($sourceTypes, [$targetType], context: $context);
    }

    /**
     * @param array<array-key,Type> $sourceTypes
     * @param array<array-key,Type> $targetTypes
     */
    #[\Override]
    public function warmingTransform(
        array $sourceTypes,
        array $targetTypes,
        Context $context,
    ): void {
        if (!$this->transformerRegistry instanceof CachingTransformerRegistry) {
            return;
        }

        if (($mappingCache = $context(MappingCache::class)) === null) {
            $mappingCache = new MappingCache();
            $context = $context->with($mappingCache);
        }

        foreach ($sourceTypes as $sourceType) {
            $searchResult = $this->transformerRegistry
                ->warmingFindBySourceAndTargetTypes([$sourceType], $targetTypes);

            foreach ($searchResult as $searchResultEntry) {
                // TransformerInterface treats Type::mixed() as "no info" and
                // expects null in that case

                $entrySourceType = $searchResultEntry->getSourceType();
                $sourceTypeForTransformer = TypeCheck::isMixed($entrySourceType) ? null : $entrySourceType;

                $entryTargetType = $searchResultEntry->getTargetType();
                $targetTypeForTransformer = TypeCheck::isMixed($entryTargetType) ? null : $entryTargetType;

                $transformerServiceId = $searchResultEntry->getTransformerServiceId();

                // get and prepare transformer
                $transformer = $this->processTransformer(
                    $this->transformerRegistry->get($transformerServiceId),
                );

                if (
                    $transformer instanceof WarmableTransformerInterface
                    && $transformer->isWarmable()
                    && $sourceTypeForTransformer !== null
                    && $targetTypeForTransformer !== null
                ) {
                    if (
                        $mappingCache->containsMapping(
                            source: $sourceTypeForTransformer,
                            target: $targetTypeForTransformer,
                            transformerServiceId: $transformerServiceId,
                        )
                    ) {
                        continue;
                    }

                    $mappingCache->saveMapping(
                        source: $sourceTypeForTransformer,
                        target: $targetTypeForTransformer,
                        transformerServiceId: $transformerServiceId,
                    );

                    $transformer->warmingTransform(
                        sourceType: $sourceTypeForTransformer,
                        targetType: $targetTypeForTransformer,
                        context: $context,
                    );
                }
            }
        }
    }
}
