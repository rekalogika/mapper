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

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\CustomMapper\ObjectMapperTable;
use Rekalogika\Mapper\CustomMapper\ObjectMapperTableFactoryInterface;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Exception\LogicException;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodRunner;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class ObjectMapperTransformer implements
    TransformerInterface,
    MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    private ObjectMapperTable $objectMapperTable;

    public function __construct(
        private SubMapperFactoryInterface $subMapperFactory,
        private ContainerInterface $serviceLocator,
        ObjectMapperTableFactoryInterface $objectMapperTableFactory,
    ) {
        $this->objectMapperTable = $objectMapperTableFactory->createObjectMapperTable();
    }

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
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
            context: $context
        );

        // target class must be valid

        $targetClass = $targetType->getClassName();

        if (
            !is_string($targetClass)
            || !\class_exists($targetClass)
        ) {
            throw new InvalidArgumentException(sprintf('Target class "%s" is not a valid class.', (string) $targetClass), context: $context);
        }

        // get source class

        if ($source === null || !\is_object($source)) {
            throw new InvalidArgumentException(
                sprintf('Source must be an object, but got: %s', \gettype($source)),
                context: $context
            );
        }

        $sourceClass = $source::class;

        $serviceMethodSpecification = $this->objectMapperTable
            ->getObjectMapper($sourceClass, $targetClass);

        if ($serviceMethodSpecification === null) {
            throw new LogicException(sprintf('No object mapper found for source class "%s" and target class "%s".', $sourceClass, $targetClass), context: $context);
        }

        $serviceMethodRunner = ServiceMethodRunner::create(
            serviceLocator: $this->serviceLocator,
            mainTransformer: $this->getMainTransformer()
        );

        return $serviceMethodRunner->run(
            serviceMethodSpecification: $serviceMethodSpecification,
            input: $source,
            context: $context
        );
    }

    public function getSupportedTransformation(): iterable
    {
        foreach ($this->objectMapperTable as $objectMapperTableEntry) {
            yield new TypeMapping(
                TypeFactory::objectOfClass($objectMapperTableEntry->getSourceClass()),
                TypeFactory::objectOfClass($objectMapperTableEntry->getTargetClass()),
            );
        }
    }
}