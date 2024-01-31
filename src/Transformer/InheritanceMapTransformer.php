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

namespace Rekalogika\Mapper\Transformer;

use Rekalogika\Mapper\Attribute\InheritanceMap;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Transformer\Exception\SourceClassNotInInheritanceMapException;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class InheritanceMapTransformer implements
    TransformerInterface,
    MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {

        # source must be an object

        if (!is_object($source)) {
            throw new InvalidArgumentException(
                \sprintf('Source must be an object, "%s" given.', \get_debug_type($source)),
                context: $context
            );
        }

        # target type must exist

        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must be specified.', context: $context);
        }

        # target must be an interface or class

        $targetClass = $targetType->getClassName();

        if (
            $targetClass === null
            || (
                !\class_exists($targetClass)
                && !\interface_exists($targetClass)
            )
        ) {
            throw new InvalidArgumentException(
                \sprintf('Target class "%s" does not exist.', $targetClass ?? 'null'),
                context: $context
            );
        }

        # gets the inheritance map

        $attributes = (new \ReflectionClass($targetClass))
            ->getAttributes(InheritanceMap::class);

        if (\count($attributes) === 0) {
            throw new InvalidArgumentException(
                \sprintf('Target class "%s" must have inheritance map.', $targetClass),
                context: $context
            );
        }

        $inheritanceMap = $attributes[0]->newInstance();

        # gets the target class from the inheritance map

        $sourceClass = \get_class($source);
        $targetClassInMap = $inheritanceMap->getTargetClassFromSourceClass($sourceClass);

        if ($targetClassInMap === null) {
            throw new SourceClassNotInInheritanceMapException($sourceClass, $targetClass);
        }

        # pass the transformation back to the main transformer

        $concreteTargetType = TypeFactory::objectOfClass($targetClassInMap);

        $result = $this->getMainTransformer()->transform(
            source: $source,
            target: null,
            sourceType: null,
            targetTypes: [$concreteTargetType],
            context: $context
        );

        # make sure $result is the correct type

        if (!is_object($result) || !is_a($result, $targetClassInMap)) {
            throw new UnexpectedValueException(
                \sprintf('Expecting an instance of "%s", "%s" given.', $targetClassInMap, \get_debug_type($result)),
                context: $context
            );
        }

        return $result;
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(
            TypeFactory::object(),
            TypeFactory::objectOfClass(InheritanceMap::class),
            true
        );
    }
}
