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

namespace Rekalogika\Mapper\SubMapper;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Specialized mapper used in MethodMapper.
 */
class SubMapper implements SubMapperInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private PropertyTypeExtractorInterface $propertyTypeExtractor,
    ) {
    }

    public function map(
        object $source,
        object|string $target,
        Context $context,
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
            context: $context
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
        string $class,
        string $property,
        Context $context,
    ): mixed {
        /** @var array<int,Type>|null */
        $targetPropertyTypes = $this->propertyTypeExtractor->getTypes(
            $class,
            $property,
        );

        /** @var mixed */
        $result = $this->getMainTransformer()->transform(
            source:$source,
            target: null,
            sourceType: null,
            targetTypes: $targetPropertyTypes ?? [],
            context: $context
        );

        return $result;
    }
}
