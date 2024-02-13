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

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Rekalogika\Mapper\Command\MappingCommand;
use Rekalogika\Mapper\Command\TryCommand;
use Rekalogika\Mapper\Command\TryPropertyCommand;
use Rekalogika\Mapper\CustomMapper\Implementation\ObjectMapperResolver;
use Rekalogika\Mapper\CustomMapper\Implementation\ObjectMapperTableFactory;
use Rekalogika\Mapper\CustomMapper\Implementation\PropertyMapperResolver;
use Rekalogika\Mapper\CustomMapper\ObjectMapperResolverInterface;
use Rekalogika\Mapper\CustomMapper\ObjectMapperTableFactoryInterface;
use Rekalogika\Mapper\CustomMapper\PropertyMapperResolverInterface;
use Rekalogika\Mapper\Implementation\Mapper;
use Rekalogika\Mapper\MainTransformer\Implementation\MainTransformer;
use Rekalogika\Mapper\MainTransformer\MainTransformerInterface;
use Rekalogika\Mapper\Mapping\Implementation\MappingFactory;
use Rekalogika\Mapper\Mapping\MappingFactoryInterface;
use Rekalogika\Mapper\ObjectCache\Implementation\ObjectCacheFactory;
use Rekalogika\Mapper\ObjectCache\ObjectCacheFactoryInterface;
use Rekalogika\Mapper\Proxy\Implementation\ProxyGenerator;
use Rekalogika\Mapper\Proxy\Implementation\ProxyRegistry;
use Rekalogika\Mapper\Proxy\ProxyAutoloaderInterface;
use Rekalogika\Mapper\Proxy\ProxyGeneratorInterface;
use Rekalogika\Mapper\Proxy\ProxyRegistryInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;
use Rekalogika\Mapper\SubMapper\Implementation\SubMapperFactory;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Implementation\ArrayLikeMetadataFactory;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\Implementation\HeuristicsEagerPropertiesResolver;
use Rekalogika\Mapper\Transformer\Implementation\ArrayToObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ClassMethodTransformer;
use Rekalogika\Mapper\Transformer\Implementation\CopyTransformer;
use Rekalogika\Mapper\Transformer\Implementation\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\Implementation\NullTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectMapperTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToArrayTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\Implementation\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\Implementation\SymfonyUidTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToTraversableTransformer;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\ObjectToObjectMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\Implementation\ProxyResolvingObjectToObjectMetadataFactory;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\TransformerInterface;
use Rekalogika\Mapper\TransformerRegistry\Implementation\TransformerRegistry;
use Rekalogika\Mapper\TransformerRegistry\TransformerRegistryInterface;
use Rekalogika\Mapper\TypeResolver\Implementation\CachingTypeResolver;
use Rekalogika\Mapper\TypeResolver\Implementation\TypeResolver;
use Rekalogika\Mapper\TypeResolver\TypeResolverInterface;
use Rekalogika\Mapper\Util\ServiceLocator;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoCacheExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyReadInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
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
use Symfony\Component\Uid\Factory\UuidFactory;

class MapperFactory
{
    /**
     * @var array<int,array{sourceClass:class-string,targetClass:class-string,property:string,service:object,method:string,extraArguments:array<int,ServiceMethodSpecification::ARGUMENT_*>}>
     */
    private array $propertyMappers = [];

    /**
     * @var array<int,array{sourceClass:class-string,targetClass:class-string,service:object,method:string,extraArguments:array<int,ServiceMethodSpecification::ARGUMENT_*>}>
     */
    private array $objectMappers = [];

    private ?Serializer $serializer = null;

    private ?NullTransformer $nullTransformer = null;
    private ?ObjectToObjectTransformer $objectToObjectTransformer = null;
    private ?ObjectToStringTransformer $objectToStringTransformer = null;
    private ?ScalarToScalarTransformer $scalarToScalarTransformer = null;
    private ?ObjectMapperTransformer $objectMapperTransformer = null;
    private ?StringToBackedEnumTransformer $stringToBackedEnumTransformer = null;
    private ?ArrayToObjectTransformer $arrayToObjectTransformer = null;
    private ?ObjectToArrayTransformer $objectToArrayTransformer = null;
    private ?DateTimeTransformer $dateTimeTransformer = null;
    private ?TraversableToArrayAccessTransformer $traversableToArrayAccessTransformer = null;
    private ?TraversableToTraversableTransformer $traversableToTraversableTransformer = null;
    private ?CopyTransformer $copyTransformer = null;
    private ?ClassMethodTransformer $classMethodTransformer = null;
    private ?SymfonyUidTransformer $symfonyUidTransformer = null;

    private CacheItemPoolInterface $propertyInfoExtractorCache;
    private null|(PropertyInfoExtractorInterface&PropertyInitializableExtractorInterface) $propertyInfoExtractor = null;
    private ?TypeResolverInterface $typeResolver = null;
    private ?ObjectToObjectMetadataFactoryInterface $objectToObjectMetadataFactory = null;
    private ?ArrayLikeMetadataFactoryInterface $arrayLikeMetadataFactory = null;
    private ?MainTransformerInterface $mainTransformer = null;
    private ?MapperInterface $mapper = null;
    private ?MappingFactoryInterface $mappingFactory = null;
    private ?ObjectCacheFactoryInterface $objectCacheFactory = null;
    private ?SubMapperFactoryInterface $subMapperFactory = null;
    private ?TransformerRegistryInterface $transformerRegistry = null;
    private ?PropertyMapperResolverInterface $propertyMapperResolver = null;
    private ?ObjectMapperTableFactoryInterface $objectMapperTableFactory = null;
    private ?ObjectMapperResolverInterface $objectMapperResolver = null;
    private ?PropertyReadInfoExtractorInterface $propertyReadInfoExtractor = null;
    private ?PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor = null;
    private ?PropertyAccessorInterface $propertyAccessor = null;
    private ?EagerPropertiesResolverInterface $eagerPropertiesResolver = null;
    private ?ProxyGeneratorInterface $proxyGenerator = null;
    private ?ProxyRegistryInterface $proxyRegistry = null;
    private ?ProxyAutoLoaderInterface $proxyAutoLoader = null;

    private ?MappingCommand $mappingCommand = null;
    private ?TryCommand $tryCommand = null;
    private ?TryPropertyCommand $tryPropertyCommand = null;
    private ?Application $application = null;

    /**
     * @param array<string,TransformerInterface> $additionalTransformers
     */
    public function __construct(
        private array $additionalTransformers = [],
        private ?ReflectionExtractor $reflectionExtractor = null,
        private ?PhpStanExtractor $phpStanExtractor = null,
        private ?NormalizerInterface $normalizer = null,
        private ?DenormalizerInterface $denormalizer = null,
        ?CacheItemPoolInterface $propertyInfoExtractorCache = null,
    ) {
        $this->propertyInfoExtractorCache = $propertyInfoExtractorCache ?? new ArrayAdapter();
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @param array<int,ServiceMethodSpecification::ARGUMENT_*> $extraArguments
     */
    public function addPropertyMapper(
        string $sourceClass,
        string $targetClass,
        string $property,
        object $service,
        string $method,
        array $extraArguments = []
    ): void {
        $this->propertyMappers[] = [
            'sourceClass' => $sourceClass,
            'targetClass' => $targetClass,
            'property' => $property,
            'service' => $service,
            'method' => $method,
            'extraArguments' => $extraArguments,
        ];
    }

    /**
     * @param class-string $sourceClass
     * @param class-string $targetClass
     * @param array<int,ServiceMethodSpecification::ARGUMENT_*> $extraArguments
     */
    public function addObjectMapper(
        string $sourceClass,
        string $targetClass,
        object $service,
        string $method,
        array $extraArguments = []
    ): void {
        $this->objectMappers[] = [
            'sourceClass' => $sourceClass,
            'targetClass' => $targetClass,
            'service' => $service,
            'method' => $method,
            'extraArguments' => $extraArguments,
        ];
    }

    public function getMapper(): MapperInterface
    {
        if (null === $this->mapper) {
            $this->mapper = new Mapper($this->getMainTransformer());
        }

        return $this->mapper;
    }

    //
    // property info
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

    private function getPropertyInfoExtractor(): PropertyInfoExtractorInterface&PropertyInitializableExtractorInterface
    {
        if ($this->propertyInfoExtractor === null) {
            $propertyInfoExtractor = new PropertyInfoExtractor(
                listExtractors: [
                    $this->getReflectionExtractor(),
                ],
                typeExtractors: [
                    $this->getPhpStanExtractor(),
                    $this->getReflectionExtractor(),
                ],
                accessExtractors: [
                    $this->getReflectionExtractor(),
                ],
                initializableExtractors: [
                    $this->getReflectionExtractor(),
                ],
            );

            $this->propertyInfoExtractor = new PropertyInfoCacheExtractor(
                $propertyInfoExtractor,
                $this->propertyInfoExtractorCache,
            );
        }

        return $this->propertyInfoExtractor;
    }

    //
    // concrete services
    //

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
                objectToObjectMetadataFactory: $this->getObjectToObjectMetadataFactory(),
                propertyMapperLocator: $this->getPropertyMapperLocator(),
                subMapperFactory: $this->getSubMapperFactory(),
                proxyRegistry: $this->getProxyRegistry(),
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

    protected function getObjectMapperTransformer(): TransformerInterface
    {
        if (null === $this->objectMapperTransformer) {
            $this->objectMapperTransformer = new ObjectMapperTransformer(
                $this->getSubMapperFactory(),
                $this->getTransformersLocator(),
                $this->getObjectMapperTableFactory(),
                $this->getObjectMapperResolver(),
            );
        }

        return $this->objectMapperTransformer;
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
            $this->traversableToArrayAccessTransformer =
                new TraversableToArrayAccessTransformer(
                    $this->getArrayLikeMetadataFactory(),
                );
        }

        return $this->traversableToArrayAccessTransformer;
    }

    protected function getTraversableToTraversableTransformer(): TransformerInterface
    {
        if (null === $this->traversableToTraversableTransformer) {
            $this->traversableToTraversableTransformer =
                new TraversableToTraversableTransformer(
                    $this->getArrayLikeMetadataFactory(),
                );
        }

        return $this->traversableToTraversableTransformer;
    }

    protected function getCopyTransformer(): TransformerInterface
    {
        if (null === $this->copyTransformer) {
            $this->copyTransformer = new CopyTransformer();
        }

        return $this->copyTransformer;
    }

    /**
     * @deprecated
     * @psalm-suppress DeprecatedClass
     */
    protected function getClassMethodTransformer(): ClassMethodTransformer
    {
        if (null === $this->classMethodTransformer) {
            $this->classMethodTransformer = new ClassMethodTransformer(
                $this->getSubMapperFactory(),
            );
        }

        return $this->classMethodTransformer;
    }

    protected function getSymfonyUidTransformer(): SymfonyUidTransformer
    {
        if (null === $this->symfonyUidTransformer) {
            $this->symfonyUidTransformer = new SymfonyUidTransformer();
        }

        return $this->symfonyUidTransformer;
    }

    //
    // other services
    //

    protected function getTypeResolver(): TypeResolverInterface
    {
        if (null === $this->typeResolver) {
            $this->typeResolver = new CachingTypeResolver(new TypeResolver());
        }

        return $this->typeResolver;
    }

    protected function getObjectToObjectMetadataFactory(): ObjectToObjectMetadataFactoryInterface
    {
        if (null === $this->objectToObjectMetadataFactory) {
            $objectToObjectMetadataFactory = new ObjectToObjectMetadataFactory(
                $this->getPropertyInfoExtractor(),
                $this->getPropertyInfoExtractor(),
                $this->getPropertyInfoExtractor(),
                $this->getPropertyMapperResolver(),
                $this->getPropertyReadInfoExtractor(),
                $this->getPropertyWriteInfoExtractor(),
                $this->getEagerPropertiesResolver(),
                $this->getProxyGenerator(),
                $this->getTypeResolver(),
            );

            $objectToObjectMetadataFactory = new ProxyResolvingObjectToObjectMetadataFactory(
                $objectToObjectMetadataFactory,
            );

            $this->objectToObjectMetadataFactory = $objectToObjectMetadataFactory;
        }

        return $this->objectToObjectMetadataFactory;
    }

    protected function getArrayLikeMetadataFactory(): ArrayLikeMetadataFactoryInterface
    {
        if (null === $this->arrayLikeMetadataFactory) {
            $this->arrayLikeMetadataFactory = new ArrayLikeMetadataFactory();
        }

        return $this->arrayLikeMetadataFactory;
    }

    /**
     * @return iterable<string,TransformerInterface>
     */
    protected function getTransformersIterator(): iterable
    {
        yield from $this->additionalTransformers;
        yield 'ScalarToScalarTransformer'
            => $this->getScalarToScalarTransformer();
        yield 'ObjectMapperTransformer'
            => $this->getObjectMapperTransformer();
        yield 'DateTimeTransformer'
            => $this->getDateTimeTransformer();
        yield 'StringToBackedEnumTransformer'
            => $this->getStringToBackedEnumTransformer();
        /**
         * @psalm-suppress DeprecatedMethod
         * @phpstan-ignore-next-line
         */
        yield 'ClassMethodTransformer' => $this->getClassMethodTransformer();

        if (class_exists(UuidFactory::class)) {
            yield 'SymfonyUidTransformer'
                => $this->getSymfonyUidTransformer();
        }

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
        yield 'CopyTransformer'
            => $this->getCopyTransformer();
    }

    protected function getTransformersLocator(): ContainerInterface
    {
        /** @psalm-suppress InvalidArgument */
        return new ServiceLocator(iterator_to_array($this->getTransformersIterator()));
    }

    protected function getMainTransformer(): MainTransformerInterface
    {
        if (null === $this->mainTransformer) {
            $this->mainTransformer = new MainTransformer(
                $this->getObjectCacheFactory(),
                $this->getTransformerRegistry(),
                $this->getTypeResolver(),
            );
        }

        return $this->mainTransformer;
    }

    protected function getMappingFactory(): MappingFactoryInterface
    {
        if (null === $this->mappingFactory) {
            $this->mappingFactory = new MappingFactory(
                $this->getTransformersIterator(),
                $this->getTypeResolver(),
            );
        }

        return $this->mappingFactory;
    }

    protected function getObjectCacheFactory(): ObjectCacheFactoryInterface
    {
        if (null === $this->objectCacheFactory) {
            $this->objectCacheFactory = new ObjectCacheFactory($this->getTypeResolver());
        }

        return $this->objectCacheFactory;
    }

    protected function getSubMapperFactory(): SubMapperFactoryInterface
    {
        if (null === $this->subMapperFactory) {
            $this->subMapperFactory = new SubMapperFactory(
                $this->getPropertyInfoExtractor(),
                $this->getPropertyAccessor(),
            );
        }

        return $this->subMapperFactory;
    }

    protected function getTransformerRegistry(): TransformerRegistryInterface
    {
        if (null === $this->transformerRegistry) {
            $this->transformerRegistry = new TransformerRegistry(
                $this->getTransformersLocator(),
                $this->getTypeResolver(),
                $this->getMappingFactory(),
            );
        }

        return $this->transformerRegistry;
    }

    protected function getPropertyMapperResolver(): PropertyMapperResolverInterface
    {
        if (null === $this->propertyMapperResolver) {
            $this->propertyMapperResolver = new PropertyMapperResolver();
            foreach ($this->propertyMappers as $propertyMapper) {
                $this->propertyMapperResolver->addPropertyMapper(
                    $propertyMapper['sourceClass'],
                    $propertyMapper['targetClass'],
                    $propertyMapper['property'],
                    $propertyMapper['service']::class,
                    $propertyMapper['method'],
                    $propertyMapper['extraArguments'],
                );
            }
        }

        return $this->propertyMapperResolver;
    }

    protected function getObjectMapperTableFactory(): ObjectMapperTableFactoryInterface
    {
        if (null === $this->objectMapperTableFactory) {
            $this->objectMapperTableFactory = new ObjectMapperTableFactory();

            foreach ($this->objectMappers as $objectMapper) {
                $this->objectMapperTableFactory->addObjectMapper(
                    $objectMapper['sourceClass'],
                    $objectMapper['targetClass'],
                    $objectMapper['service']::class,
                    $objectMapper['method'],
                    $objectMapper['extraArguments'],
                );
            }
        }

        return $this->objectMapperTableFactory;
    }

    protected function getObjectMapperResolver(): ObjectMapperResolverInterface
    {
        if (null === $this->objectMapperResolver) {
            $this->objectMapperResolver = new ObjectMapperResolver(
                $this->getObjectMapperTableFactory()
            );
        }

        return $this->objectMapperResolver;
    }

    protected function getPropertyMapperLocator(): ContainerInterface
    {
        $services = [];

        foreach ($this->propertyMappers as $propertyMapper) {
            $service = $propertyMapper['service'];
            $class = $service::class;
            $services[$class] = $service;
        }

        return new ServiceLocator($services);
    }

    protected function getPropertyReadInfoExtractor(): PropertyReadInfoExtractorInterface
    {
        if (null === $this->propertyReadInfoExtractor) {
            $this->propertyReadInfoExtractor = $this->getReflectionExtractor();
        }

        return $this->propertyReadInfoExtractor;
    }

    protected function getPropertyWriteInfoExtractor(): PropertyWriteInfoExtractorInterface
    {
        if (null === $this->propertyWriteInfoExtractor) {
            $this->propertyWriteInfoExtractor = $this->getReflectionExtractor();
        }

        return $this->propertyWriteInfoExtractor;
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    protected function getEagerPropertiesResolver(): EagerPropertiesResolverInterface
    {
        if (null === $this->eagerPropertiesResolver) {
            $this->eagerPropertiesResolver = new HeuristicsEagerPropertiesResolver();
        }

        return $this->eagerPropertiesResolver;
    }

    protected function getProxyGenerator(): ProxyGeneratorInterface
    {
        if (null === $this->proxyGenerator) {
            $this->proxyGenerator = new ProxyGenerator();
        }

        return $this->proxyGenerator;
    }

    protected function getProxyRegistry(): ProxyRegistryInterface
    {
        if (null === $this->proxyRegistry) {
            $this->proxyRegistry = new ProxyRegistry('/tmp/rekalogika-mapper');
        }

        return $this->proxyRegistry;
    }

    protected function getProxyAutoLoader(): ProxyAutoLoaderInterface
    {
        if (null === $this->proxyAutoLoader) {
            $proxyRegistry = $this->getProxyRegistry();
            assert($proxyRegistry instanceof ProxyAutoloaderInterface);
            $this->proxyAutoLoader = $proxyRegistry;
        }

        return $this->proxyAutoLoader;
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
                $this->getTransformerRegistry(),
                $this->getTypeResolver()
            );
        }

        return $this->tryCommand;
    }

    protected function getTryPropertyCommand(): TryPropertyCommand
    {
        if (null === $this->tryPropertyCommand) {
            $this->tryPropertyCommand = new TryPropertyCommand(
                $this->getTransformerRegistry(),
                $this->getTypeResolver(),
                $this->getPropertyInfoExtractor(),
            );
        }

        return $this->tryPropertyCommand;
    }

    public function getApplication(): Application
    {
        if (null === $this->application) {
            $this->application = new Application();
            $this->application->add($this->getMappingCommand());
            $this->application->add($this->getTryCommand());
            $this->application->add($this->getTryPropertyCommand());
        }

        return $this->application;
    }
}
