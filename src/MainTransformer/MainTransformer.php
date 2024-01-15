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

namespace Rekalogika\Mapper\MainTransformer;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Context\ContextMemberNotFoundException;
use Rekalogika\Mapper\MainTransformer\Exception\CannotFindTransformerException;
use Rekalogika\Mapper\MainTransformer\Exception\TransformerReturnsUnexpectedValueException;
use Rekalogika\Mapper\MainTransformer\Model\Path;
use Rekalogika\Mapper\ObjectCache\Exception\CachedTargetObjectNotFoundException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\ObjectCache\ObjectCacheFactoryInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MixedType;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Exception\RefuseToTransformException;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\TypeCheck;
use Symfony\Component\PropertyInfo\Type;

class MainTransformer implements MainTransformerInterface
{
    public const OBJECT_CACHE = 'object_cache';

    public function __construct(
        private ObjectCacheFactoryInterface $objectCacheFactory,
        private TransformerRegistryInterface $transformerRegistry,
        private TypeResolverInterface $typeResolver,
    ) {
    }

    private function processTransformer(
        TransformerInterface $transformer
    ): TransformerInterface {
        if ($transformer instanceof MainTransformerAwareInterface) {
            return $transformer->withMainTransformer($this);
        }

        return $transformer;
    }

    /**
     * @param array<array-key,Type|MixedType> $types
     * @return array<int,Type|MixedType>
     */
    private function getSimpleTypes(
        array $types
    ): array {
        $simpleTypes = [];

        foreach ($types as $type) {
            foreach ($this->typeResolver->getSimpleTypes($type) as $simpleType) {
                $simpleTypes[] = $simpleType;
            }
        }

        return $simpleTypes;
    }

    public function transform(
        mixed $source,
        mixed $target,
        array $targetTypes,
        Context $context,
        string $path = null,
    ): mixed {
        // if target is provided, guess the type from it.
        // if target is not provided, use the provided target type. if it is
        // also not provided, then the target type is mixed.

        if ($target === null) {
            if (count($targetTypes) === 0) {
                $targetTypes = [MixedType::instance()];
            }
        } else {
            if (count($targetTypes) === 0) {
                $targetTypes = [$this->typeResolver->guessTypeFromVariable($target)];
            }
        }

        // get or create object cache

        try {
            $objectCache = $context(ObjectCache::class);
        } catch (ContextMemberNotFoundException) {
            $objectCache = $this->objectCacheFactory->createObjectCache();
            $context = $context->with($objectCache);
        }

        // initialize path it it doesn't exist

        try {
            $pathContext = $context(Path::class);
        } catch (ContextMemberNotFoundException) {
            $pathContext = Path::create();
            $context = $context->with($pathContext);
        }

        // append path if provided

        if ($path !== null) {
            if (!\str_starts_with($path, '[')) {
                $path = '.' . $path;
            }
            $context = $context->with($pathContext->append($path));
        }

        // guess the source type

        $sourceTypes = [$this->typeResolver->guessTypeFromVariable($source)];

        // gets simple target types from the provided target type

        $targetTypes = $this->getSimpleTypes($targetTypes);

        // search for the matching transformers according to the source and
        // target types

        $searchResult = $this->transformerRegistry
            ->findBySourceAndTargetTypes(
                $sourceTypes,
                $targetTypes
            );

        // loop over the result and transform the source to the target

        foreach ($searchResult as $searchEntry) {
            // if the target exists, we make sure it is of the same type as
            // the target type of the search entry

            if ($target !== null) {
                if (
                    !TypeCheck::isVariableInstanceOf($target, $searchEntry->getTargetType())
                ) {
                    continue;
                }
            }

            // inject the main transformer to the transformer if it is
            // MainTransformerAwareInterface
            $transformer = $this->processTransformer($searchEntry->getTransformer());

            // TransformerInterface doesn't accept MixedType, so we need to
            // convert it to null

            $sourceType = $searchEntry->getSourceType();
            $sourceTypeForTransformer = $sourceType instanceof MixedType ? null : $sourceType;

            $targetType = $searchEntry->getTargetType();
            $targetTypeForTransformer = $targetType instanceof MixedType ? null : $targetType;

            // if the target type is cached, return it. otherwise, pre-cache it

            if ($targetTypeForTransformer !== null) {
                try {
                    return $objectCache->getTarget(
                        $source,
                        $targetTypeForTransformer,
                        $context
                    );
                } catch (CachedTargetObjectNotFoundException) {
                    $objectCache->preCache(
                        $source,
                        $targetTypeForTransformer,
                        $context
                    );
                }
            }

            // transform the source to the target

            try {
                /** @var mixed */
                $result = $transformer->transform(
                    source: $source,
                    target: $target,
                    sourceType: $sourceTypeForTransformer,
                    targetType: $targetTypeForTransformer,
                    context: $context
                );
            } catch (RefuseToTransformException) {
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
                    $source,
                    $targetTypeForTransformer,
                    $result,
                    $context,
                    true,
                );
            }

            return $result;
        }

        throw new CannotFindTransformerException($sourceTypes, $targetTypes, context: $context);
    }
}
