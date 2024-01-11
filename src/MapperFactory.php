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

namespace Rekalogika\Mapper;

use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Command\MappingCommand;
use Rekalogika\Mapper\Command\TryCommand;
use Rekalogika\Mapper\Contracts\TransformerInterface;
use Rekalogika\Mapper\Mapping\MappingFactory;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\Model\ServiceLocator;
use Rekalogika\Mapper\Transformer\ArrayToObjectTransformer;
use Rekalogika\Mapper\Transformer\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\NullTransformer;
use Rekalogika\Mapper\Transformer\ObjectToArrayTransformer;
use Rekalogika\Mapper\Transformer\ObjectToObjectTransformer;
use Rekalogika\Mapper\Transformer\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\TraversableToTraversableTransformer;
use Symfony\Component\Console\Application;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DataUriNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

class MapperFactory
{
    private ?Serializer $serializer = null;

    private ?NullTransformer $nullTransformer = null;
    private ?ObjectToObjectTransformer $objectToObjectTransformer = null;
    private ?ObjectToStringTransformer $objectToStringTransformer = null;
    private ?ScalarToScalarTransformer $scalarToScalarTransformer = null;
    private ?StringToBackedEnumTransformer $stringToBackedEnumTransformer = null;
    private ?ArrayToObjectTransformer $arrayToObjectTransformer = null;
    private ?ObjectToArrayTransformer $objectToArrayTransformer = null;
    private ?DateTimeTransformer $dateTimeTransformer = null;
    private ?TraversableToArrayAccessTransformer $traversableToArrayAccessTransformer = null;
    private ?TraversableToTraversableTransformer $traversableToTraversableTransformer = null;

    private ?PropertyTypeExtractorInterface $propertyTypeExtractor = null;
    private ?TypeStringHelper $typeStringHelper = null;
    private ?MainTransformer $mainTransformer = null;
    private ?MapperInterface $mapper = null;
    private ?MappingFactoryInterface $mappingFactory = null;

    private ?MappingCommand $mappingCommand = null;
    private ?TryCommand $tryCommand = null;
    private ?Application $application = null;

    /**
     * @param array<string,TransformerInterface> $additionalTransformers
     */
    public function __construct(
        private array $additionalTransformers = [],
        private ?ReflectionExtractor $reflectionExtractor = null,
        private ?PhpStanExtractor $phpStanExtractor = null,
        private ?PropertyAccessor $propertyAccessor = null,
        private ?NormalizerInterface $normalizer = null,
        private ?DenormalizerInterface $denormalizer = null,
    ) {
    }

    public function getMapper(): MapperInterface
    {
        if (null === $this->mapper) {
            $this->mapper = new Mapper($this->getMainTransformer());
        }

        return $this->mapper;
    }

    //
    // concrete services
    //

    private function getReflectionExtractor(): ReflectionExtractor
    {
        if (null === $this->reflectionExtractor) {
            $this->reflectionExtractor = new ReflectionExtractor();
        }

        return $this->reflectionExtractor;
    }

    private function getPhpStanExtractor(): PropertyTypeExtractorInterface
    {
        if (null === $this->phpStanExtractor) {
            $this->phpStanExtractor = new PhpStanExtractor();
        }

        return $this->phpStanExtractor;
    }

    private function getConcretePropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    private function getSerializer(): Serializer
    {
        if (null === $this->serializer) {
            $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());

            $this->serializer = new Serializer([
                new UidNormalizer(),
                new DateTimeNormalizer(),
                new DateTimeZoneNormalizer(),
                new DateIntervalNormalizer(),
                new BackedEnumNormalizer(),
                new DataUriNormalizer(),
                new JsonSerializableNormalizer(),
                new ObjectNormalizer($classMetadataFactory),
            ], []);
        }

        return $this->serializer;
    }

    //
    // interfaces
    //

    private function getPropertyListExtractor(): PropertyListExtractorInterface
    {
        return $this->getReflectionExtractor();
    }

    private function getPropertyTypeExtractor(): PropertyTypeExtractorInterface
    {
        if ($this->propertyTypeExtractor === null) {
            $this->propertyTypeExtractor = new PropertyInfoExtractor(
                typeExtractors: [
                    $this->getPhpStanExtractor(),
                    $this->getReflectionExtractor(),
                ],
            );
        }

        return $this->propertyTypeExtractor;
    }

    private function getPropertyInitializableExtractor(): PropertyInitializableExtractorInterface
    {
        return $this->getReflectionExtractor();
    }

    private function getPropertyAccessExtractor(): PropertyAccessExtractorInterface
    {
        return $this->getReflectionExtractor();
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->getConcretePropertyAccessor();
    }

    private function getNormalizer(): NormalizerInterface
    {
        if ($this->normalizer !== null) {
            return $this->normalizer;
        }

        return $this->getSerializer();
    }

    private function getDenormalizer(): DenormalizerInterface
    {
        if ($this->denormalizer !== null) {
            return $this->denormalizer;
        }

        return $this->getSerializer();
    }

    //
    // transformers
    //

    protected function getNullTransformer(): TransformerInterface
    {
        if (null === $this->nullTransformer) {
            $this->nullTransformer = new NullTransformer();
        }

        return $this->nullTransformer;
    }

    protected function getObjectToObjectTransformer(): TransformerInterface
    {
        if (null === $this->objectToObjectTransformer) {
            $this->objectToObjectTransformer = new ObjectToObjectTransformer(
                $this->getPropertyListExtractor(),
                $this->getPropertyTypeExtractor(),
                $this->getPropertyInitializableExtractor(),
                $this->getPropertyAccessExtractor(),
                $this->getPropertyAccessor()
            );
        }

        return $this->objectToObjectTransformer;
    }

    protected function getObjectToStringTransformer(): TransformerInterface
    {
        if (null === $this->objectToStringTransformer) {
            $this->objectToStringTransformer = new ObjectToStringTransformer();
        }

        return $this->objectToStringTransformer;
    }

    protected function getScalarToScalarTransformer(): TransformerInterface
    {
        if (null === $this->scalarToScalarTransformer) {
            $this->scalarToScalarTransformer = new ScalarToScalarTransformer();
        }

        return $this->scalarToScalarTransformer;
    }

    protected function getStringToBackedEnumTransformer(): TransformerInterface
    {
        if (null === $this->stringToBackedEnumTransformer) {
            $this->stringToBackedEnumTransformer = new StringToBackedEnumTransformer();
        }

        return $this->stringToBackedEnumTransformer;
    }

    protected function getArrayToObjectTransformer(): TransformerInterface
    {
        if (null === $this->arrayToObjectTransformer) {
            $this->arrayToObjectTransformer = new ArrayToObjectTransformer(
                $this->getDenormalizer()
            );
        }

        return $this->arrayToObjectTransformer;
    }

    protected function getObjectToArrayTransformer(): TransformerInterface
    {
        if (null === $this->objectToArrayTransformer) {
            $this->objectToArrayTransformer = new ObjectToArrayTransformer(
                $this->getNormalizer()
            );
        }

        return $this->objectToArrayTransformer;
    }

    protected function getDateTimeTransformer(): TransformerInterface
    {
        if (null === $this->dateTimeTransformer) {
            $this->dateTimeTransformer = new DateTimeTransformer();
        }

        return $this->dateTimeTransformer;
    }

    protected function getTraversableToArrayAccessTransformer(): TransformerInterface
    {
        if (null === $this->traversableToArrayAccessTransformer) {
            $this->traversableToArrayAccessTransformer = new TraversableToArrayAccessTransformer();
        }

        return $this->traversableToArrayAccessTransformer;
    }

    protected function getTraversableToTraversableTransformer(): TransformerInterface
    {
        if (null === $this->traversableToTraversableTransformer) {
            $this->traversableToTraversableTransformer = new TraversableToTraversableTransformer();
        }

        return $this->traversableToTraversableTransformer;
    }

    //
    // other services
    //

    protected function getTypeStringHelper(): TypeStringHelper
    {
        if (null === $this->typeStringHelper) {
            $this->typeStringHelper = new TypeStringHelper();
        }

        return $this->typeStringHelper;
    }

    /**
     * @return iterable<string,TransformerInterface>
     */
    protected function getTransformersIterator(): iterable
    {
        yield from $this->additionalTransformers;
        yield 'ScalarToScalarTransformer'
            => $this->getScalarToScalarTransformer();
        yield 'DateTimeTransformer'
            => $this->getDateTimeTransformer();
        yield 'StringToBackedEnumTransformer'
            => $this->getStringToBackedEnumTransformer();
        yield 'ObjectToStringTransformer'
            => $this->getObjectToStringTransformer();
        yield 'TraversableToArrayAccessTransformer'
            => $this->getTraversableToArrayAccessTransformer();
        yield 'TraversableToTraversableTransformer'
            => $this->getTraversableToTraversableTransformer();
        yield 'ObjectToArrayTransformer'
            => $this->getObjectToArrayTransformer();
        yield 'ArrayToObjectTransformer'
            => $this->getArrayToObjectTransformer();
        yield 'ObjectToObjectTransformer'
            => $this->getObjectToObjectTransformer();
        yield 'NullTransformer'
            => $this->getNullTransformer();
    }

    protected function getTransformersLocator(): ContainerInterface
    {
        /** @psalm-suppress InvalidArgument */
        return new ServiceLocator(iterator_to_array($this->getTransformersIterator()));
    }

    protected function getMainTransformer(): MainTransformer
    {
        if (null === $this->mainTransformer) {
            $this->mainTransformer = new MainTransformer(
                $this->getTransformersLocator(),
                $this->getTypeStringHelper(),
                $this->getMappingFactory(),
            );
        }

        return $this->mainTransformer;
    }

    protected function getMappingFactory(): MappingFactoryInterface
    {
        if (null === $this->mappingFactory) {
            $this->mappingFactory = new MappingFactory(
                $this->getTransformersIterator()
            );
        }

        return $this->mappingFactory;
    }

    //
    // command
    //

    protected function getMappingCommand(): MappingCommand
    {
        if (null === $this->mappingCommand) {
            $this->mappingCommand = new MappingCommand(
                $this->getMappingFactory()
            );
        }

        return $this->mappingCommand;
    }

    protected function getTryCommand(): TryCommand
    {
        if (null === $this->tryCommand) {
            $this->tryCommand = new TryCommand(
                $this->getMainTransformer(),
                $this->getTypeStringHelper()
            );
        }

        return $this->tryCommand;
    }

    public function getApplication(): Application
    {
        if (null === $this->application) {
            $this->application = new Application();
            $this->application->add($this->getMappingCommand());
            $this->application->add($this->getTryCommand());
        }

        return $this->application;
    }
}
