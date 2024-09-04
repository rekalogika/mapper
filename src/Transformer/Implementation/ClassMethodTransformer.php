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

namespace Rekalogika\Mapper\Transformer\Implementation;

use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\MethodMapper\MapFromObjectInterface;
use Rekalogika\Mapper\MethodMapper\MapToObjectInterface;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

/**
 * @deprecated
 */
final class ClassMethodTransformer implements
    TransformerInterface,
    MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private SubMapperFactoryInterface $subMapperFactory,
    ) {}

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        // target type must not be null

        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // prepare subMapper

        $subMapper = $this->subMapperFactory->createSubMapper(
            mainTransformer: $this->getMainTransformer(),
            source: $source,
            targetType: $targetType,
            context: $context,
        );

        // target class must be valid

        $targetClass = $targetType->getClassName();

        if (
            !\is_string($targetClass)
            || !class_exists($targetClass)
        ) {
            throw new InvalidArgumentException(\sprintf('Target class "%s" is not a valid class.', (string) $targetClass), context: $context);
        }


        if (is_a($targetClass, MapFromObjectInterface::class, true)) {

            // map from object to self path

            if (!\is_object($source)) {
                throw new InvalidArgumentException(\sprintf('Source must be object, "%s" given', get_debug_type($source)), context: $context);
            }

            $result = $targetClass::mapFromObject($source, $subMapper, $context);
        } elseif ($source instanceof MapToObjectInterface) {

            // map self to object path

            if (!\is_object($target)) {
                $target = $targetClass;
            }

            $result = $source->mapToObject($target, $subMapper, $context);
        } else {
            throw new LogicException('Should not reach here', context: $context);
        }

        return $result;
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(
            TypeFactory::objectOfClass(MapToObjectInterface::class),
            TypeFactory::object(),
            true,
        );

        yield new TypeMapping(
            TypeFactory::object(),
            TypeFactory::objectOfClass(MapFromObjectInterface::class),
            true,
        );
    }
}
