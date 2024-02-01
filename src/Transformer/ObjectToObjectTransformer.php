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
use Rekalogika\Mapper\Exception\InvalidArgumentException;
use Rekalogika\Mapper\Exception\UnexpectedValueException;
use Rekalogika\Mapper\ObjectCache\ObjectCache;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareInterface;
use Rekalogika\Mapper\Transformer\Contracts\MainTransformerAwareTrait;
use Rekalogika\Mapper\Transformer\Contracts\TransformerInterface;
use Rekalogika\Mapper\Transformer\Contracts\TypeMapping;
use Rekalogika\Mapper\Transformer\Exception\ClassNotInstantiableException;
use Rekalogika\Mapper\Transformer\Exception\InstantiationFailureException;
use Rekalogika\Mapper\Transformer\Exception\NotAClassException;
use Rekalogika\Mapper\Transformer\Exception\UnableToReadException;
use Rekalogika\Mapper\Transformer\Exception\UnableToWriteException;
use Rekalogika\Mapper\Transformer\Exception\UninitializedSourcePropertyException;
use Rekalogika\Mapper\Transformer\Exception\UnsupportedPropertyMappingException;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\ObjectToObjectMetadata;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\PropertyMapping;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\ReadMode;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\Visibility;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Contracts\WriteMode;
use Rekalogika\Mapper\Util\TypeCheck;
use Rekalogika\Mapper\Util\TypeFactory;
use Rekalogika\Mapper\Util\TypeGuesser;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;

final class ObjectToObjectTransformer implements TransformerInterface, MainTransformerAwareInterface
{
    use MainTransformerAwareTrait;

    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private ObjectToObjectMetadataFactoryInterface $objectToObjectMetadataFactory,
        private ContainerInterface $propertyMapperLocator,
    ) {
    }

    public function transform(
        mixed $source,
        mixed $target,
        ?Type $sourceType,
        ?Type $targetType,
        Context $context
    ): mixed {
        if ($targetType === null) {
            throw new InvalidArgumentException('Target type must not be null.', context: $context);
        }

        // verify source

        if (!is_object($source)) {
            throw new InvalidArgumentException(sprintf('The source must be an object, "%s" given.', get_debug_type($source)), context: $context);
        }

        $sourceType = TypeGuesser::guessTypeFromVariable($source);
        $sourceClass = $sourceType->getClassName();

        if (null === $sourceClass || !\class_exists($sourceClass)) {
            throw new InvalidArgumentException("Cannot get the class name for the source type.", context: $context);
        }

        // verify target

        $targetClass = $targetType->getClassName();

        if (null === $targetClass) {
            throw new InvalidArgumentException("Cannot get the class name for the target type.", context: $context);
        }

        if (!\class_exists($targetClass)) {
            throw new NotAClassException($targetClass, context: $context);
        }

        // if sourceType and targetType are the same, just return the source

        if (null === $target && TypeCheck::isSomewhatIdentical($sourceType, $targetType)) {
            return $source;
        }

        // get the object to object mapping metadata

        $objectToObjectMetadata = $this->objectToObjectMetadataFactory
            ->createObjectToObjectMetadata($sourceClass, $targetClass, $context);

        // initialize target if target is null

        if (null === $target) {
            $target = $this->instantiateTarget(
                source: $source,
                objectToObjectMetadata: $objectToObjectMetadata,
                context: $context
            );
        } else {
            if (!is_object($target)) {
                throw new InvalidArgumentException(sprintf('The target must be an object, "%s" given.', get_debug_type($target)), context: $context);
            }
        }

        // save object to cache

        $context(ObjectCache::class)
            ->saveTarget($source, $targetType, $target, $context);

        // map properties

        $this->writeTarget(
            source: $source,
            target: $target,
            objectToObjectMetadata: $objectToObjectMetadata,
            context: $context
        );

        return $target;
    }

    private function instantiateTarget(
        object $source,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context
    ): object {
        $targetClass = $objectToObjectMetadata->getTargetClass();

        // check if class is valid & instantiable

        if (!$objectToObjectMetadata->isInstantiable()) {
            throw new ClassNotInstantiableException($targetClass, context: $context);
        }

        // gets the mapping and loop over the mapping

        $propertyMappings = $objectToObjectMetadata->getPropertyMappings();

        $constructorArguments = [];

        /** @var array<int,string> */
        $unsetSourceProperties = [];

        foreach ($propertyMappings as $propertyMapping) {
            if ($propertyMapping->getTargetWriteMode() !== WriteMode::Constructor) {
                continue;
            }

            try {
                /** @var mixed */
                $targetPropertyValue = $this->transformValue(
                    propertyMapping: $propertyMapping,
                    source: $source,
                    target: null,
                    context: $context
                );

                /** @psalm-suppress MixedAssignment */
                $constructorArguments[$propertyMapping->getTargetProperty()]
                    = $targetPropertyValue;
            } catch (UninitializedSourcePropertyException $e) {
                $sourceProperty = $e->getPropertyName();
                $unsetSourceProperties[] = $sourceProperty;

                continue;
            } catch (UnsupportedPropertyMappingException $e) {
                continue;
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($targetClass);

            return $reflectionClass->newInstanceArgs($constructorArguments);
        } catch (\TypeError | \ReflectionException $e) {
            throw new InstantiationFailureException(
                source: $source,
                targetClass: $targetClass,
                constructorArguments: $constructorArguments,
                unsetSourceProperties: $unsetSourceProperties,
                previous: $e,
                context: $context
            );
        }
    }

    private function writeTarget(
        object $source,
        object $target,
        ObjectToObjectMetadata $objectToObjectMetadata,
        Context $context
    ): void {
        foreach ($objectToObjectMetadata->getPropertyMappings() as $propertyMapping) {
            $targetWriteMode = $propertyMapping->getTargetWriteMode();
            $targetWriteVisibility = $propertyMapping->getTargetWriteVisibility();

            if (
                $targetWriteMode !== WriteMode::Method
                && $targetWriteMode !== WriteMode::Property
            ) {
                continue;
            }

            if ($targetWriteVisibility !== Visibility::Public) {
                continue;
            }

            try {
                assert(is_object($target));

                /** @var mixed */
                $targetPropertyValue = $this->transformValue(
                    propertyMapping: $propertyMapping,
                    source: $source,
                    target: $target,
                    context: $context
                );
            } catch (UninitializedSourcePropertyException $e) {
                continue;
            } catch (UnsupportedPropertyMappingException $e) {
                continue;
            }

            $targetProperty = $propertyMapping->getTargetProperty();

            try {
                $this->propertyAccessor
                    ->setValue($target, $targetProperty, $targetPropertyValue);
            } catch (AccessException | UnexpectedTypeException $e) {
                throw new UnableToWriteException(
                    $source,
                    $target,
                    $target,
                    $targetProperty,
                    $e,
                    context: $context
                );
            }
        }
    }

    /**
     * @throws UnsupportedPropertyMappingException
     * @throws UninitializedSourcePropertyException
     */
    private function transformValue(
        PropertyMapping $propertyMapping,
        object $source,
        ?object $target,
        Context $context
    ): mixed {
        $sourceProperty = $propertyMapping->getSourceProperty();
        $targetProperty = $propertyMapping->getTargetProperty();
        $targetTypes = $propertyMapping->getTargetTypes();

        // if a custom property mapper is set, then use it

        if ($propertyMapperPointer = $propertyMapping->getPropertyMapper()) {
            /** @var object */
            $propertyMapper = $this->propertyMapperLocator
                ->get($propertyMapperPointer->getServiceId());

            /**
             * @psalm-suppress MixedAssignment
             * @psalm-suppress MixedMethodCall
             */
            $targetPropertyValue = $propertyMapper->{$propertyMapperPointer
                ->getMethod()}($source);

            /** @psalm-suppress MixedAssignment */
            return $targetPropertyValue;
        }

        // if source property name is null, continue. there is nothing to
        // transform

        if ($sourceProperty === null) {
            throw new UnsupportedPropertyMappingException();
        }

        // get the value of the source property

        try {
            $sourceReadMode = $propertyMapping->getSourceReadMode();
            $sourceReadVisibility = $propertyMapping->getSourceReadVisibility();

            if (
                $sourceReadMode !== ReadMode::None
                && $sourceReadVisibility === Visibility::Public
            ) {
                /** @var mixed */
                $sourcePropertyValue = $this->propertyAccessor
                    ->getValue($source, $sourceProperty);
            } else {
                $sourcePropertyValue = null;
            }
        } catch (NoSuchPropertyException $e) {
            // if source property is not found, then it is an error
            throw new UnexpectedValueException(
                sprintf(
                    'Cannot get value of source property "%s::$%s".',
                    get_debug_type($source),
                    $sourceProperty
                ),
                previous: $e,
                context: $context
            );
        } catch (UninitializedPropertyException $e) {
            // if source property is unset, we skip it
            throw new UninitializedSourcePropertyException($sourceProperty);
        } catch (AccessException | UnexpectedTypeException $e) {
            // otherwise, it is an error
            throw new UnableToReadException(
                $source,
                $target,
                $source,
                $sourceProperty,
                $e,
                context: $context
            );
        }

        // do simple scalar to scalar transformation if possible

        $targetScalarType = $propertyMapping->getTargetScalarType();

        if ($targetScalarType !== null && is_scalar($sourcePropertyValue)) {
            switch ($targetScalarType) {
                case 'int':
                    $targetPropertyValue = (int) $sourcePropertyValue;
                    break;
                case 'float':
                    $targetPropertyValue = (float) $sourcePropertyValue;
                    break;
                case 'string':
                    $targetPropertyValue = (string) $sourcePropertyValue;
                    break;
                case 'bool':
                    $targetPropertyValue = (bool) $sourcePropertyValue;
                    break;
            }

            return $targetPropertyValue;
        }

        // get the value of the target property

        try {
            $targetReadMode = $propertyMapping->getTargetReadMode();
            $targetReadVisibility = $propertyMapping->getTargetReadVisibility();

            if (
                $targetReadMode !== ReadMode::None
                && $targetReadVisibility === Visibility::Public
                && $target !== null
            ) {
                /** @var mixed */
                $targetPropertyValue = $this->propertyAccessor
                    ->getValue($target, $targetProperty);
            } else {
                $targetPropertyValue = null;
            }
        } catch (UninitializedPropertyException $e) {
            $targetPropertyValue = null;
        } catch (NoSuchPropertyException | AccessException | UnexpectedTypeException $e) {
            $targetPropertyValue = null;
        }

        // guess source type, and get the compatible type from metadata, so
        // we can preserve generics information

        $guessedSourceType = TypeGuesser::guessTypeFromVariable($sourcePropertyValue);
        $sourceType = $propertyMapping->getCompatibleSourceType($guessedSourceType);

        // transform the value

        /** @var mixed */
        $targetPropertyValue = $this->getMainTransformer()->transform(
            source: $sourcePropertyValue,
            target: $targetPropertyValue,
            sourceType: $sourceType,
            targetTypes: $targetTypes,
            context: $context,
            path: $targetProperty,
        );

        return $targetPropertyValue;
    }

    public function getSupportedTransformation(): iterable
    {
        yield new TypeMapping(TypeFactory::object(), TypeFactory::object(), true);
    }
}
