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

use Rekalogika\Mapper\Cache\WarmableMainTransformerInterface;
use Rekalogika\Mapper\Cache\WarmableTransformerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\MapperOptions;
use Rekalogika\Mapper\MainTransformer\Exception\CannotFindTransformerException;
use Rekalogika\Mapper\MainTransformer\Exception\CircularReferenceException;
use Rekalogika\Mapper\MainTransformer\Exception\TransformerReturnsUnexpectedValueException;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\MainTransformer\Model\DebugContext;
use Rekalogika\Mapper\MainTransformer\Model\Path;
use Rekalogika\Mapper\ObjectCache\Exception\CachedTargetObjectNotFoundException;
use Rekalogika\Mapper\ObjectCache\Exception\CircularReferenceException as ObjectCacheCircularReferenceException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\ObjectCache\ObjectCacheFactoryInterface;
use Rekalogika\Mapper\Transformer\Exception\RefuseToTransformException;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\MixedType;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\TransformerRegistry\Implementation\CachingTransformerRegistry;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\PropertyInfo\Type;
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
        private readonly TypeResolverInterface $typeResolver,
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
        array $targetTypes,
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
            if ($targetTypes === []) {
                $targetTypes = [MixedType::instance()];
            }
        } elseif ($targetTypes === []) {
            $targetTypes = [TypeGuesser::guessTypeFromVariable($target)];
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

        // gets simple target types from the provided target type

        $targetTypes = $this->typeResolver->getSimpleTypes($targetTypes);

        // if debug, inject debug context

        if ($this->debug) {
            $context = $context->with(
                new DebugContext(
                    sourceType: $sourceTypes[0],
                    targetTypes: $targetTypes,
                    sourceTypeGuessed: $isSourceTypeGuessed,
                ),
            );
        }

        // search for the matching transformers according to the source and
        // target types

        $searchResult = $this->transformerRegistry
            ->findBySourceAndTargetTypes(
                $sourceTypes,
                $targetTypes,
            );

        // loop over the result and transform the source to the target

        foreach ($searchResult as $searchResultEntry) {
            // if the target exists, we make sure it is of the same type as
            // the target type of the search entry, if not continue to the next
            // search entry

            if ($target !== null && !TypeCheck::isVariableInstanceOf($target, $searchResultEntry->getTargetType())) {
                continue;
            }

            // TransformerInterface doesn't accept MixedType, so we need to
            // convert it to null

            $sourceType = $searchResultEntry->getSourceType();
            $sourceTypeForTransformer = $sourceType instanceof MixedType ? null : $sourceType;

            $targetType = $searchResultEntry->getTargetType();
            $targetTypeForTransformer = $targetType instanceof MixedType ? null : $targetType;

            // if the target type is cached, return it. otherwise, pre-cache it

            if ($targetTypeForTransformer !== null) {
                try {
                    return $objectCache
                        ->getTarget($source, $targetTypeForTransformer);
                } catch (CachedTargetObjectNotFoundException) {
                    $objectCache
                        ->preCache($source, $targetTypeForTransformer);
                } catch (ObjectCacheCircularReferenceException $e) {
                    throw new CircularReferenceException(
                        source: $source,
                        targetType: $targetTypeForTransformer,
                        context: $context,
                        previous: $e,
                    );
                }
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
                !TypeCheck::isVariableInstanceOf($result, $targetType)
            ) {
                throw new TransformerReturnsUnexpectedValueException($source, $targetType, $result, $transformer, $context);
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

        throw new CannotFindTransformerException($sourceTypes, $targetTypes, context: $context);
    }

    /**
     * @param array<array-key,Type> $sourceTypes
     * @param array<array-key,Type> $targetTypes
     */
    public function warmTransform(
        array $sourceTypes,
        array $targetTypes,
    ): void {
        if (!$this->transformerRegistry instanceof CachingTransformerRegistry) {
            return;
        }

        foreach ($sourceTypes as $sourceType) {
            $searchResult = $this->transformerRegistry
                ->warmFindBySourceAndTargetTypes([$sourceType], $targetTypes);

            foreach ($searchResult as $searchResultEntry) {
                // TransformerInterface doesn't accept MixedType, so we need to
                // convert it to null

                $sourceType = $searchResultEntry->getSourceType();
                $sourceTypeForTransformer = $sourceType instanceof MixedType ? null : $sourceType;

                $targetType = $searchResultEntry->getTargetType();
                $targetTypeForTransformer = $targetType instanceof MixedType ? null : $targetType;

                // get and prepare transformer
                $transformer = $this->processTransformer(
                    $this->transformerRegistry->get(
                        $searchResultEntry->getTransformerServiceId(),
                    ),
                );

                if (
                    $transformer instanceof WarmableTransformerInterface
                    && $sourceTypeForTransformer !== null
                    && $targetTypeForTransformer !== null
                ) {
                    $transformer->warmTransform(
                        sourceType: $sourceTypeForTransformer,
                        targetType: $targetTypeForTransformer,
                    );
                }
            }
        }
    }
}
