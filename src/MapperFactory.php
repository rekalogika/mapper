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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\UuidInterface;
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
use Rekalogika\Mapper\Proxy\Implementation\PhpProxyFactory;
use Rekalogika\Mapper\Proxy\Implementation\ProxyFactory;
use Rekalogika\Mapper\Proxy\Implementation\ProxyGenerator;
use Rekalogika\Mapper\Proxy\Implementation\ProxyMetadataFactory;
use Rekalogika\Mapper\Proxy\Implementation\ProxyRegistry;
use Rekalogika\Mapper\Proxy\Implementation\VarExporterProxyFactory;
use Rekalogika\Mapper\Proxy\ProxyAutoloaderInterface;
use Rekalogika\Mapper\Proxy\ProxyFactoryInterface;
use Rekalogika\Mapper\Proxy\ProxyGeneratorInterface;
use Rekalogika\Mapper\Proxy\ProxyMetadataFactoryInterface;
use Rekalogika\Mapper\Proxy\ProxyRegistryInterface;
use Rekalogika\Mapper\ServiceMethod\ServiceMethodSpecification;
use Rekalogika\Mapper\SubMapper\Implementation\SubMapperFactory;
use Rekalogika\Mapper\SubMapper\SubMapperFactoryInterface;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\ArrayLikeMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\ArrayLikeMetadata\Implementation\ArrayLikeMetadataFactory;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\EagerPropertiesResolverInterface;
use Rekalogika\Mapper\Transformer\EagerPropertiesResolver\Implementation\HeuristicsEagerPropertiesResolver;
use Rekalogika\Mapper\Transformer\Implementation\ArrayObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\CopyTransformer;
use Rekalogika\Mapper\Transformer\Implementation\DateTimeTransformer;
use Rekalogika\Mapper\Transformer\Implementation\NullToNullTransformer;
use Rekalogika\Mapper\Transformer\Implementation\NullTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectMapperTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToObjectTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ObjectToStringTransformer;
use Rekalogika\Mapper\Transformer\Implementation\PresetTransformer;
use Rekalogika\Mapper\Transformer\Implementation\RamseyUuidTransformer;
use Rekalogika\Mapper\Transformer\Implementation\ScalarToScalarTransformer;
use Rekalogika\Mapper\Transformer\Implementation\StringToBackedEnumTransformer;
use Rekalogika\Mapper\Transformer\Implementation\SymfonyUidTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToArrayAccessTransformer;
use Rekalogika\Mapper\Transformer\Implementation\TraversableToTraversableTransformer;
use Rekalogika\Mapper\Transformer\MetadataUtil\MetadataUtilLocator;
use Rekalogika\Mapper\Transformer\ObjectToObjectMetadata\ObjectToObjectMetadataFactoryInterface;
use Rekalogika\Mapper\Transformer\Processor\ObjectProcessor\DefaultObjectProcessorFactory;
use Rekalogika\Mapper\Transformer\Processor\ObjectProcessorFactoryInterface;
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
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @api
 */
class MapperFactory
{
    /**
     * @var array<int,array{sourceClass:class-string,targetClass:class-string,property:string,service:object,method:string,hasExistingTarget:bool,ignoreUninitialized:bool,extraArguments:array<int,ServiceMethodSpecification::ARGUMENT_*>}>
     */
    private array $propertyMappers = [];

    /**
     * @var array<int,array{sourceClass:class-string,targetClass:class-string,service:object,method:string,hasExistingTarget:bool,extraArguments:array<int,ServiceMethodSpecification::ARGUMENT_*>}>
     */
    private array $objectMappers = [];

    private ?NullToNullTransformer $nullToNullTransformer = null;

    private ?NullTransformer $nullTransformer = null;

    private ?ObjectToObjectTransformer $objectToObjectTransformer = null;

    private ?ObjectToStringTransformer $objectToStringTransformer = null;

    private ?ScalarToScalarTransformer $scalarToScalarTransformer = null;

    private ?ObjectMapperTransformer $objectMapperTransformer = null;

    private ?StringToBackedEnumTransformer $stringToBackedEnumTransformer = null;

    private ?ArrayObjectTransformer $arrayObjectTransformer = null;

    private ?DateTimeTransformer $dateTimeTransformer = null;

    private ?TraversableToArrayAccessTransformer $traversableToArrayAccessTransformer = null;

    private ?TraversableToTraversableTransformer $traversableToTraversableTransformer = null;

    private ?CopyTransformer $copyTransformer = null;

    private ?SymfonyUidTransformer $symfonyUidTransformer = null;

    private ?RamseyUuidTransformer $ramseyUuidTransformer = null;

    private ?PresetTransformer $presetTransformer = null;

    private null|(PropertyInfoExtractorInterface&PropertyInitializableExtractorInterface) $propertyInfoExtractor = null;

    private ?TypeResolverInterface $typeResolver = null;

    private ?MetadataUtilLocator $metadataUtilLocator = null;

    private ?ObjectToObjectMetadataFactoryInterface $objectToObjectMetadataFactory = null;

    private ?ArrayLikeMetadataFactoryInterface $arrayLikeMetadataFactory = null;

    private ?MainTransformerInterface $mainTransformer = null;

    private ?MapperInterface $mapper = null;

    private ?IterableMapperInterface $iterableMapper = null;

    private ?MappingFactoryInterface $mappingFactory = null;

    private ?ObjectCacheFactoryInterface $objectCacheFactory = null;

    private ?SubMapperFactoryInterface $subMapperFactory = null;

    private ?TransformerRegistryInterface $transformerRegistry = null;

    private ?ContainerInterface $propertyMapperLocator = null;

    private ?PropertyMapperResolverInterface $propertyMapperResolver = null;

    private ?ObjectMapperTableFactoryInterface $objectMapperTableFactory = null;

    private ?ContainerInterface $objectMapperLocator = null;

    private ?ObjectMapperResolverInterface $objectMapperResolver = null;

    private ?PropertyReadInfoExtractorInterface $propertyReadInfoExtractor = null;

    private ?PropertyWriteInfoExtractorInterface $propertyWriteInfoExtractor = null;

    private ?PropertyAccessorInterface $propertyAccessor = null;

    private ?EagerPropertiesResolverInterface $eagerPropertiesResolver = null;

    private ?ProxyGeneratorInterface $proxyGenerator = null;

    private ?ProxyRegistryInterface $proxyRegistry = null;

    private ?ProxyAutoloaderInterface $proxyAutoLoader = null;

    private ?ProxyFactoryInterface $proxyFactory = null;

    private ?VarExporterProxyFactory $varExporterProxyFactory = null;

    private ?PhpProxyFactory $phpProxyFactory = null;

    private ?ProxyMetadataFactoryInterface $proxyMetadataFactory = null;

    private ?ObjectProcessorFactoryInterface $objectProcessorFactory = null;

    private ?MappingCommand $mappingCommand = null;

    private ?TryCommand $tryCommand = null;

    private ?TryPropertyCommand $tryPropertyCommand = null;

    private ?Application $application = null;

    /**
     * @param array<string,TransformerInterface> $additionalTransformers
     */
    public function __construct(
        private readonly array $additionalTransformers = [],
        private ?ReflectionExtractor $reflectionExtractor = null,
        private ?PhpStanExtractor $phpStanExtractor = null,
        private readonly CacheItemPoolInterface $propertyInfoExtractorCache = new ArrayAdapter(),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

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
        bool $hasExistingTarget,
        bool $ignoreUninitialized,
        array $extraArguments = [],
    ): void {
        $this->propertyMappers[] = [
            'sourceClass' => $sourceClass,
            'targetClass' => $targetClass,
            'property' => $property,
            'service' => $service,
            'method' => $method,
            'hasExistingTarget' => $hasExistingTarget,
            'ignoreUninitialized' => $ignoreUninitialized,
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
        bool $hasExistingTarget,
        array $extraArguments = [],
    ): void {
        $this->objectMappers[] = [
            'sourceClass' => $sourceClass,
            'targetClass' => $targetClass,
            'service' => $service,
            'method' => $method,
            'hasExistingTarget' => $hasExistingTarget,
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

    public function getIterableMapper(): IterableMapperInterface
    {
        if (null === $this->iterableMapper) {
            $this->iterableMapper = new Mapper($this->getMainTransformer());
        }

        return $this->iterableMapper;
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
    // transformers
    //

    protected function getNullToNullTransformer(): TransformerInterface
    {
        if (null === $this->nullToNullTransformer) {
            $this->nullToNullTransformer = new NullToNullTransformer();
        }

        return $this->nullToNullTransformer;
    }

    protected function getNullTransformer(): TransformerInterface
    {
        if (null === $this->nullTransformer) {
            $this->nullTransformer = new NullTransformer();
        }

        return $this->nullTransformer;
    }

    protected function getObjectToObjectTransformer(): TransformerInterface
    {
        return $this->objectToObjectTransformer ??= new ObjectToObjectTransformer(
            $this->getObjectToObjectMetadataFactory(),
            $this->getObjectProcessorFactory(),
        );
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
                $this->getObjectMapperLocator(),
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

    protected function getArrayObjectTransformer(): TransformerInterface
    {
        $objectToObjectTransformer = $this->getObjectToObjectTransformer();
        \assert($objectToObjectTransformer instanceof ObjectToObjectTransformer);

        if (null === $this->arrayObjectTransformer) {
            $this->arrayObjectTransformer = new ArrayObjectTransformer(
                $objectToObjectTransformer,
            );
        }

        return $this->arrayObjectTransformer;
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

    protected function getSymfonyUidTransformer(): SymfonyUidTransformer
    {
        if (null === $this->symfonyUidTransformer) {
            $this->symfonyUidTransformer = new SymfonyUidTransformer();
        }

        return $this->symfonyUidTransformer;
    }

    protected function getRamseyUuidTransformer(): RamseyUuidTransformer
    {
        if (null === $this->ramseyUuidTransformer) {
            $this->ramseyUuidTransformer = new RamseyUuidTransformer();
        }

        return $this->ramseyUuidTransformer;
    }

    protected function getPresetTransformer(): PresetTransformer
    {
        if (null === $this->presetTransformer) {
            $this->presetTransformer = new PresetTransformer();
        }

        return $this->presetTransformer;
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

    protected function getMetadataUtilLocator(): MetadataUtilLocator
    {
        return $this->metadataUtilLocator ??= new MetadataUtilLocator(
            propertyListExtractor: $this->getPropertyInfoExtractor(),
            propertyTypeExtractor: $this->getPropertyInfoExtractor(),
            propertyReadInfoExtractor: $this->getPropertyReadInfoExtractor(),
            propertyWriteInfoExtractor: $this->getPropertyWriteInfoExtractor(),
            typeResolver: $this->getTypeResolver(),
            eagerPropertiesResolver: $this->getEagerPropertiesResolver(),
            proxyFactory: $this->getProxyFactory(),
            propertyMapperResolver: $this->getPropertyMapperResolver(),
        );
    }

    protected function getObjectToObjectMetadataFactory(): ObjectToObjectMetadataFactoryInterface
    {
        return $this->objectToObjectMetadataFactory
            ??= $this->getMetadataUtilLocator()
            ->getObjectToObjectMetadataFactory();
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
        yield 'NullToNullTransformer' => $this->getNullToNullTransformer();

        yield from $this->additionalTransformers;

        yield 'ScalarToScalarTransformer'
            => $this->getScalarToScalarTransformer();
        yield 'ObjectMapperTransformer'
            => $this->getObjectMapperTransformer();
        yield 'DateTimeTransformer'
            => $this->getDateTimeTransformer();
        yield 'StringToBackedEnumTransformer'
            => $this->getStringToBackedEnumTransformer();

        if (class_exists(UuidFactory::class)) {
            yield 'SymfonyUidTransformer'
                => $this->getSymfonyUidTransformer();
        }

        if (interface_exists(UuidInterface::class)) {
            yield 'RamseyUuidTransformer'
                => $this->getRamseyUuidTransformer();
        }

        yield 'ObjectToStringTransformer'
            => $this->getObjectToStringTransformer();

        yield 'PresetTransformer'
            => $this->getPresetTransformer();

        yield 'TraversableToArrayAccessTransformer'
            => $this->getTraversableToArrayAccessTransformer();
        yield 'TraversableToTraversableTransformer'
            => $this->getTraversableToTraversableTransformer();

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
                objectCacheFactory: $this->getObjectCacheFactory(),
                transformerRegistry: $this->getTransformerRegistry(),
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
                $this->getProxyFactory(),
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
                    sourceClass: $propertyMapper['sourceClass'],
                    targetClass: $propertyMapper['targetClass'],
                    property: $propertyMapper['property'],
                    serviceId: $propertyMapper['service']::class,
                    method: $propertyMapper['method'],
                    hasExistingTarget: $propertyMapper['hasExistingTarget'],
                    ignoreUninitialized: $propertyMapper['ignoreUninitialized'],
                    extraArguments: $propertyMapper['extraArguments'],
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
                    sourceClass: $objectMapper['sourceClass'],
                    targetClass: $objectMapper['targetClass'],
                    serviceId: $objectMapper['service']::class,
                    method: $objectMapper['method'],
                    hasExistingTarget: $objectMapper['hasExistingTarget'],
                    extraArguments: $objectMapper['extraArguments'],
                );
            }
        }

        return $this->objectMapperTableFactory;
    }

    protected function getObjectMapperLocator(): ContainerInterface
    {
        if ($this->objectMapperLocator !== null) {
            return $this->objectMapperLocator;
        }

        $services = [];

        foreach ($this->objectMappers as $objectMapper) {
            $service = $objectMapper['service'];
            $class = $service::class;
            $services[$class] = $service;
        }

        return $this->objectMapperLocator = new ServiceLocator($services);
    }

    protected function getObjectMapperResolver(): ObjectMapperResolverInterface
    {
        if (null === $this->objectMapperResolver) {
            $this->objectMapperResolver = new ObjectMapperResolver(
                $this->getObjectMapperTableFactory(),
            );
        }

        return $this->objectMapperResolver;
    }

    protected function getPropertyMapperLocator(): ContainerInterface
    {
        if ($this->propertyMapperLocator !== null) {
            return $this->propertyMapperLocator;
        }

        $services = [];

        foreach ($this->propertyMappers as $propertyMapper) {
            $service = $propertyMapper['service'];
            $class = $service::class;
            $services[$class] = $service;
        }

        return $this->propertyMapperLocator = new ServiceLocator($services);
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
            $this->proxyRegistry = new ProxyRegistry(
                proxyDirectory: '/tmp/rekalogika-mapper',
            );
        }

        return $this->proxyRegistry;
    }

    protected function getProxyAutoLoader(): ProxyAutoloaderInterface
    {
        if (null === $this->proxyAutoLoader) {
            $proxyRegistry = $this->getProxyRegistry();
            \assert($proxyRegistry instanceof ProxyAutoloaderInterface);
            $this->proxyAutoLoader = $proxyRegistry;
        }

        return $this->proxyAutoLoader;
    }

    protected function getVarExporterProxyFactory(): VarExporterProxyFactory
    {
        if (null === $this->varExporterProxyFactory) {
            $this->varExporterProxyFactory = new VarExporterProxyFactory(
                $this->getProxyRegistry(),
                $this->getProxyGenerator(),
                $this->getProxyMetadataFactory(),
            );
        }

        return $this->varExporterProxyFactory;
    }

    protected function getPhpProxyFactory(): PhpProxyFactory
    {
        if (null === $this->phpProxyFactory) {
            $this->phpProxyFactory = new PhpProxyFactory(
                $this->getProxyMetadataFactory(),
            );
        }

        return $this->phpProxyFactory;
    }

    protected function getProxyFactory(): ProxyFactoryInterface
    {
        if (null === $this->proxyFactory) {
            $this->proxyFactory = new ProxyFactory(
                $this->getVarExporterProxyFactory(),
                $this->getPhpProxyFactory(),
            );
        }

        return $this->proxyFactory;
    }

    protected function getProxyMetadataFactory(): ProxyMetadataFactoryInterface
    {
        if (null === $this->proxyMetadataFactory) {
            $this->proxyMetadataFactory = new ProxyMetadataFactory();
        }

        return $this->proxyMetadataFactory;
    }

    //
    // transformer processor
    //

    protected function getObjectProcessorFactory(): ObjectProcessorFactoryInterface
    {
        return $this->objectProcessorFactory ??= new DefaultObjectProcessorFactory(
            propertyMapperLocator: $this->getPropertyMapperLocator(),
            subMapperFactory: $this->getSubMapperFactory(),
            proxyFactory: $this->getProxyFactory(),
            propertyAccessor: $this->getPropertyAccessor(),
            logger: $this->logger,
        );
    }

    //
    // command
    //

    protected function getMappingCommand(): MappingCommand
    {
        if (null === $this->mappingCommand) {
            $this->mappingCommand = new MappingCommand(
                $this->getMappingFactory(),
            );
        }

        return $this->mappingCommand;
    }

    protected function getTryCommand(): TryCommand
    {
        if (null === $this->tryCommand) {
            $this->tryCommand = new TryCommand(
                $this->getTransformerRegistry(),
                $this->getTypeResolver(),
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
