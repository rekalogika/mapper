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

namespace Rekalogika\Mapper\SubMapper\Implementation;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\SubMapper\SubMapperInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @internal
 */
class SubMapper implements SubMapperInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
        private PropertyAccessorInterface $propertyAccessor,
        private mixed $source,
        private Type $targetType,
        private Context $context,
    ) {
    }

    public function map(
        object $source,
        object|string $target,
        ?Context $context,
    ): object {
        if (is_object($target)) {
            $targetClass = $target::class;
            $targetObject = $target;
        } else {
            $targetClass = $target;
            $targetObject = null;
        }

        /** @var mixed */
        $result = $this->getMainTransformer()->transform(
            source: $source,
            target: $targetObject,
            sourceType: null,
            targetTypes: [TypeFactory::objectOfClass($targetClass)],
            context: $context ?? $this->context
        );

        if (is_object($target)) {
            $targetClass = $target::class;
        } else {
            $targetClass = $target;
        }

        if ($result instanceof $targetClass) {
            return $result;
        }

        throw new UnexpectedValueException(sprintf('The mapper did not return the variable of expected class, expecting "%s", returned "%s".', $targetClass, get_debug_type($target)), context: $context);
    }

    public function mapForProperty(
        object $source,
        string|object $containing,
        string $property,
        ?Context $context,
    ): mixed {
        if (is_object($containing)) {
            $containingObject = $containing;
            $containingClass = $containing::class;

            try {
                /** @var mixed */
                $targetPropertyValue = $this->propertyAccessor
                    ->getValue($containingObject, $property);

                if (is_scalar($targetPropertyValue)) {
                    $targetPropertyValue = null;
                }
            } catch (ExceptionInterface $e) {
                $targetPropertyValue = null;
            }
        } else {
            $containingClass = $containing;
            $containingObject = null;
            $targetPropertyValue = null;
        }


        /** @var array<int,Type>|null */
        $targetPropertyTypes = $this->propertyTypeExtractor
            ->getTypes($containingClass, $property);

        /** @var mixed */
        $result = $this->getMainTransformer()->transform(
            source: $source,
            target: $targetPropertyValue,
            sourceType: null,
            targetTypes: $targetPropertyTypes ?? [],
            context: $context ?? $this->context
        );

        return $result;
    }

    public function cache(mixed $target): void
    {
        ($this->context)(ObjectCache::class)->saveTarget(
            source: $this->source,
            targetType: $this->targetType,
            target: $target
        );
    }
}
