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

use Rekalogika\Mapper\CacheWarmer\WarmableMainTransformerInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\CacheWarmer\WarmableTransformerInterface;
use Rekalogika\Mapper\Context\Context;
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Transformer\Exception\NotAClassException;
use Rekalogika\Mapper\Transformer\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\Processor\ObjectProcessorFactoryInterface;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\Transformer\TypeMapping;
use Rekalogika\Mapper\Util\TypeFactory;
use Symfony\Component\PropertyInfo\Type;

final class ObjectToObjectTransformer implements
    TransformerInterface,
    MainTransformerAwareInterface,
    WarmableTransformerInterface
{
    use MainTransformerAwareTrait;

    private ?ObjectProcessorFactoryInterface $objectProcessorFactoryWithMainTransformer = null;

    public function __construct(
        private readonly ObjectToObjectMetadataFactoryInterface $objectToObjectMetadataFactory,
        private readonly ObjectProcessorFactoryInterface $objectProcessorFactory,
    ) {}

    private function getObjectProcessorFactory(): ObjectProcessorFactoryInterface
    {
        return $this->objectProcessorFactoryWithMainTransformer
            ??= $this->objectProcessorFactory
            ->withMainTransformer($this->getMainTransformer());
    }

    #[\Override]
    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context,
    ): mixed {
        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // verify source

        if (!\is_object($source)) {
            throw new InvalidArgumentException(\sprintf('The source must be an object, "%s" given.', get_debug_type($source)), context: $context);
        }

        $sourceClass = $source::class;

        // verify target

        if (\is_object($target)) {
            $targetClass = $target::class;
        } else {
            $targetClass = $targetType->getClassName();

            if (null === $targetClass) {
                throw new InvalidArgumentException("Cannot get the class name for the target type.", context: $context);
            }

            if (!class_exists($targetClass) && !interface_exists($targetClass)) {
                throw new NotAClassException($targetClass, context: $context);
            }
        }

        // if sourceType and targetType are the same, just return the source

        if (null === $target && $targetClass === $sourceClass && !$source instanceof \stdClass) {
            return $source;
        }

        // get the object to object mapping metadata

        $objectToObjectMetadata = $this->objectToObjectMetadataFactory
            ->createObjectToObjectMetadata($sourceClass, $targetClass);

        // type checking

        if ($target !== null && !\is_object($target)) {
            throw new InvalidArgumentException(\sprintf('The target must be an object, "%s" given.', get_debug_type($target)), context: $context);
        }

        // transform

        return $this->getObjectProcessorFactory()
            ->getObjectProcessor($objectToObjectMetadata)
            ->transform(
                source: $source,
                target: $target,
                targetType: $targetType,
                context: $context,
            );
    }

    #[\Override]
    public function warmingTransform(
        Type $sourceType,
        Type $targetType,
        Context $context,
    ): void {
        if (!$this->objectToObjectMetadataFactory instanceof WarmableObjectToObjectMetadataFactoryInterface) {
            return;
        }

        $sourceClass = $sourceType->getClassName();

        if (null === $sourceClass || !class_exists($sourceClass)) {
            return;
        }

        $targetClass = $targetType->getClassName();

        if (null === $targetClass || !class_exists($targetClass)) {
            return;
        }

        try {
            $objectToObjectMetadata = $this->objectToObjectMetadataFactory
                ->warmingCreateObjectToObjectMetadata($sourceClass, $targetClass);
        } catch (\Throwable) {
            return;
        }

        $mainTransformer = $this->getMainTransformer();

        if (!$mainTransformer instanceof WarmableMainTransformerInterface) {
            return;
        }

        foreach ($objectToObjectMetadata->getPropertyMappings() as $propertyMapping) {
            $sourceTypes = $propertyMapping->getSourceTypes();
            $targetTypes = $propertyMapping->getTargetTypes();

            if ($sourceTypes === [] || $targetTypes === []) {
                continue;
            }

            $mainTransformer->warmingTransform($sourceTypes, $targetTypes, $context);
        }
    }

    #[\Override]
    public function isWarmable(): bool
    {
        return true;
    }

    #[\Override]
    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object(), true);
    }
}
